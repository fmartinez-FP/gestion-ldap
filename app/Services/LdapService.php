<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use App\Models\Aplicacion;
use App\Mail\NuevoUsuario;
use Illuminate\Support\Facades\Mail;

class LdapService
{
    private \LDAP\Connection $ldap;
    private string $baseDn;
    private string $usersDn;
    private string $groupsDn;

    public function __construct()
    {
        $this->baseDn   = config('ldap.connections.default.base_dn');
        $this->usersDn  = "ou=users,{$this->baseDn}";
        $this->groupsDn = "ou=groups,{$this->baseDn}";

        $host     = config('ldap.connections.default.hosts.0');
        $port     = config('ldap.connections.default.port', 389);
        $username = config('ldap.connections.default.username');
        $password = config('ldap.connections.default.password');

        $conn = ldap_connect("ldap://{$host}:{$port}");
        if (!$conn) {
            throw new \RuntimeException('No se pudo conectar al servidor LDAP.');
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);
        self::applyTls($conn);

        if (!ldap_bind($conn, $username, $password)) {
            throw new \RuntimeException('Fallo al autenticar con el servidor LDAP: ' . ldap_error($conn));
        }

        $this->ldap = $conn;
    }

    // =========================================================================
    // AUTENTICACIÓN DE USUARIOS (portal)
    // =========================================================================

    /**
     * Abre una conexión temporal y prueba el bind con las credenciales del usuario.
     * No usa el bind de admin. Devuelve true si las credenciales son válidas.
     */
    public static function verifyUserBind(string $uid, string $password): bool
    {
        $baseDn  = config('ldap.connections.default.base_dn');
        $usersDn = "ou=users,{$baseDn}";
        $userDn  = "uid={$uid},{$usersDn}";
        $host    = config('ldap.connections.default.hosts.0');
        $port    = config('ldap.connections.default.port', 389);

        $conn = ldap_connect("ldap://{$host}:{$port}");
        if (!$conn) {
            return false;
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);
        self::applyTls($conn);

        // Suprimir warning de PHP en caso de credenciales inválidas (LDAP devuelve error)
        $bound = @ldap_bind($conn, $userDn, $password);
        @ldap_unbind($conn);

        return (bool) $bound;
    }

    /**
     * Autentica un usuario LDAP por UID + contraseña.
     * Si las credenciales son válidas y la cuenta está activa, devuelve el array
     * con los datos del usuario (incluyendo grupos obtenidos por búsqueda inversa).
     * Devuelve null si las credenciales son incorrectas o la cuenta está inactiva.
     *
     * @throws \RuntimeException Si el servidor LDAP no está disponible.
     */
    public static function authenticateUser(string $uid, string $password): ?array
    {
        if (!self::verifyUserBind($uid, $password)) {
            return null;
        }

        // Credenciales OK — obtener perfil completo con bind de admin
        $service = new self();
        $user    = $service->getUser($uid);

        if (!$user || !$user['activo']) {
            return null;
        }

        // getUserGroups hace búsqueda inversa en los grupos (más fiable que memberOf overlay)
        $user['grupos'] = $service->getUserGroups($uid);

        return $user;
    }


    /**
     * Comprueba si ppolicy exige al usuario cambiar su contraseña.
     * Consulta el atributo operacional pwdReset con bind de admin.
     * Devuelve true si pwdReset: TRUE está presente en la entrada del usuario.
     */
    public static function hasPwdReset(string $uid): bool
    {
        try {
            $service = new self();
            $result  = ldap_search($service->ldap, $service->usersDn, "(uid={$uid})", ['pwdReset']);
            if (!$result) return false;

            $entries = ldap_get_entries($service->ldap, $result);
            if ($entries['count'] === 0) return false;

            return isset($entries[0]['pwdreset'][0])
                && strtoupper($entries[0]['pwdreset'][0]) === 'TRUE';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Cambia la contraseña usando el PROPIO bind del usuario (no el de admin).
     *
     * Diferencia crítica con changePassword():
     * - changePassword() verifica el bind pero usa $this->ldap (admin) para el modify.
     *   Con ppolicy pwdMustChange:TRUE, el admin modificando userPassword vuelve a
     *   escribir pwdReset:TRUE → bucle infinito.
     * - Este método hace el ldap_modify con la conexión del usuario, lo que hace
     *   que ppolicy borre pwdReset:TRUE automáticamente al detectar que es el
     *   propio titular quien modifica su contraseña.
     */
    public static function changeUserOwnPassword(string $uid, string $currentPassword, string $newPassword): bool
    {
        $baseDn  = config('ldap.connections.default.base_dn');
        $usersDn = "ou=users,{$baseDn}";
        $userDn  = "uid={$uid},{$usersDn}";
        $host    = config('ldap.connections.default.hosts.0');
        $port    = config('ldap.connections.default.port', 389);

        $conn = ldap_connect("ldap://{$host}:{$port}");
        if (!$conn) return false;

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);
        self::applyTls($conn);

        // Bind como el usuario — valida la contraseña actual
        if (!@ldap_bind($conn, $userDn, $currentPassword)) {
            @ldap_unbind($conn);
            return false;
        }

        // RFC 3062 — Password Modify Extended Operation.
        // ldap_modify directo sobre userPassword NO dispara el hook de ppolicy
        // que borra pwdReset. Solo lo hace esta operación extendida nativa.
        $ok = @ldap_exop_passwd($conn, $userDn, $currentPassword, $newPassword);
        @ldap_unbind($conn);

        if (!$ok) return false;

        // Garantía explícita: borrar pwdReset vía admin.
        // ldap_mod_del con array vacío NO genera el MODIFY DELETE correcto en PHP.
        // ldap_modify_batch con LDAP_MODIFY_BATCH_REMOVE_ALL es el equivalente
        // exacto a "delete: pwdReset" en LDIF, que sí elimina el atributo entero.
        try {
            $service = new self();
            ldap_modify_batch($service->ldap, $userDn, [[
                'attrib'  => 'pwdReset',
                'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
            ]]);
        } catch (\Throwable) {
            // No bloquea — la contraseña ya cambió correctamente
        }

        return true;
    }

    /**
     * Cambia la contraseña de un usuario LDAP.
     * Verifica la contraseña actual mediante bind directo antes de modificar.
     */
    public function changePassword(string $uid, string $currentPassword, string $newPassword): bool
    {
        if (!self::verifyUserBind($uid, $currentPassword)) {
            return false;
        }

        $dn     = "uid={$uid},{$this->usersDn}";
        $modify = ['userPassword' => $this->hashPassword($newPassword)];

        if (!ldap_modify($this->ldap, $dn, $modify)) {
            Log::error("LDAP changePassword {$uid}: " . ldap_error($this->ldap));
            return false;
        }

        return true;
    }

    // =========================================================================
    // USUARIOS
    // =========================================================================

    public function getAllUsers(): array
    {
        $filter = '(objectClass=inetOrgPerson)';
        $attrs  = ['uid', 'cn', 'givenName', 'sn', 'mail', 'shadowExpire', 'memberOf'];

        $result = ldap_search($this->ldap, $this->usersDn, $filter, $attrs);
        if (!$result) return [];

        $entries = ldap_get_entries($this->ldap, $result);
        $users   = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $users[] = $this->parseEntry($entries[$i]);
        }

        usort($users, fn($a, $b) => strcmp($a['nombre_completo'], $b['nombre_completo']));
        return $users;
    }

    public function getUser(string $uid): ?array
    {
        $filter  = "(uid={$uid})";
        $attrs   = ['uid', 'cn', 'givenName', 'sn', 'mail', 'shadowExpire', 'memberOf'];
        $result  = ldap_search($this->ldap, $this->usersDn, $filter, $attrs);
        if (!$result) return null;

        $entries = ldap_get_entries($this->ldap, $result);
        if ($entries['count'] === 0) return null;

        return $this->parseEntry($entries[0]);
    }

    public function createUser(array $data): bool
    {
        $uid  = $data['uid'];
        $dn   = "uid={$uid},{$this->usersDn}";
        $hash = $this->hashPassword($data['password']);

        $entry = [
            'objectClass'  => ['inetOrgPerson', 'organizationalPerson', 'person', 'shadowAccount'],
            'uid'          => $uid,
            'cn'           => $data['nombre_completo'],
            'givenName'    => $data['nombre'],
            'sn'           => $data['apellidos'],
            'mail'         => $data['mail'],
            'userPassword' => $hash,
            'shadowExpire' => $data['activo'] ? '-1' : '0',
        ];

        if (!ldap_add($this->ldap, $dn, $entry)) {
            Log::error("LDAP createUser {$uid}: " . ldap_error($this->ldap));
            return false;
        }

        foreach ($data['grupos'] ?? [] as $grupo) {
            $this->addToGroup($uid, $grupo);
        }

        AuditLog::record('create_user', $uid, ['mail' => $data['mail'], 'grupos' => $data['grupos'] ?? []]);

        if (!empty($data['mail'])) {
            try {
                $appNames = Aplicacion::whereIn('codigo', $data['grupos'] ?? [])
                    ->orderBy('nombre')->pluck('nombre')->toArray();
                Mail::to($data['mail'])->send(new NuevoUsuario(
                    nombre:   $data['nombre_completo'],
                    username: $uid,
                    password: $data['password'],
                    apps:     $appNames,
                ));
            } catch (\Throwable $e) {
                Log::warning("Email bienvenida {$uid}: " . $e->getMessage());
            }
        }

        return true;
    }

    public function updateUser(string $uid, array $data): bool
    {
        $dn     = "uid={$uid},{$this->usersDn}";
        $modify = [
            'cn'           => $data['nombre_completo'],
            'givenName'    => $data['nombre'],
            'sn'           => $data['apellidos'],
            'mail'         => $data['mail'],
            'shadowExpire' => $data['activo'] ? '-1' : '0',
        ];

        if (!empty($data['password'])) {
            $modify['userPassword'] = $this->hashPassword($data['password']);
        }

        if (!ldap_modify($this->ldap, $dn, $modify)) {
            Log::error("LDAP updateUser {$uid}: " . ldap_error($this->ldap));
            return false;
        }

        $currentGroups = $this->getUserGroups($uid);
        $newGroups     = $data['grupos'] ?? [];

        foreach (array_diff($newGroups, $currentGroups) as $add) {
            $this->addToGroup($uid, $add);
        }
        foreach (array_diff($currentGroups, $newGroups) as $remove) {
            $this->removeFromGroup($uid, $remove);
        }

        AuditLog::record('update_user', $uid, ['grupos_nuevos' => $newGroups]);
        return true;
    }

    public function deleteUser(string $uid): bool
    {
        foreach ($this->getUserGroups($uid) as $grupo) {
            $this->removeFromGroup($uid, $grupo);
        }

        $dn = "uid={$uid},{$this->usersDn}";
        if (!ldap_delete($this->ldap, $dn)) {
            Log::error("LDAP deleteUser {$uid}: " . ldap_error($this->ldap));
            return false;
        }

        AuditLog::record('delete_user', $uid);
        return true;
    }

    public function setUserActive(string $uid, bool $active): bool
    {
        $dn     = "uid={$uid},{$this->usersDn}";
        $modify = ['shadowExpire' => $active ? '-1' : '0'];
        $result = ldap_modify($this->ldap, $dn, $modify);
        if ($result) AuditLog::record($active ? 'activate_user' : 'deactivate_user', $uid);
        return $result;
    }

    public function resetPassword(string $uid): bool
    {
        $dn     = "uid={$uid},{$this->usersDn}";
        $modify = ['userPassword' => $this->hashPassword($uid . '1234')];
        $result = ldap_modify($this->ldap, $dn, $modify);
        if ($result) AuditLog::record('reset_password', $uid);
        return $result;
    }

    // =========================================================================
    // GRUPOS
    // =========================================================================

    public function getUserGroups(string $uid): array
    {
        $userDn  = "uid={$uid},{$this->usersDn}";
        $filter  = "(member={$userDn})";
        $result  = ldap_search($this->ldap, $this->groupsDn, $filter, ['cn']);
        if (!$result) return [];

        $entries = ldap_get_entries($this->ldap, $result);
        $groups  = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $groups[] = $entries[$i]['cn'][0];
        }

        return $groups;
    }

    public function addToGroup(string $uid, string $groupCn): bool
    {
        $groupDn = "cn={$groupCn},{$this->groupsDn}";
        $userDn  = "uid={$uid},{$this->usersDn}";
        return ldap_mod_add($this->ldap, $groupDn, ['member' => $userDn]);
    }

    public function removeFromGroup(string $uid, string $groupCn): bool
    {
        $groupDn = "cn={$groupCn},{$this->groupsDn}";
        $userDn  = "uid={$uid},{$this->usersDn}";
        return ldap_mod_del($this->ldap, $groupDn, ['member' => $userDn]);
    }

    public function getAllGroups(): array
    {
        $filter  = '(objectClass=groupOfNames)';
        $result  = ldap_search($this->ldap, $this->groupsDn, $filter, ['cn', 'description', 'member']);
        if (!$result) return [];

        $entries = ldap_get_entries($this->ldap, $result);
        $groups  = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $e        = $entries[$i];
            $groups[] = [
                'cn'          => $e['cn'][0],
                'description' => $e['description'][0] ?? '',
                'miembros'    => $e['member']['count'] ?? 0,
            ];
        }

        return $groups;
    }

    public function createGroup(string $cn, string $description): bool
    {
        $groupDn = "cn={$cn},{$this->groupsDn}";
        $adminDn = "cn=admin,{$this->baseDn}";

        $entry = [
            'objectClass' => ['groupOfNames'],
            'cn'          => $cn,
            'description' => $description,
            'member'      => $adminDn,
        ];

        $result = ldap_add($this->ldap, $groupDn, $entry);
        if ($result) AuditLog::record('create_group', $cn, ['description' => $description]);
        return $result;
    }

    public function deleteGroup(string $cn): bool
    {
        $result = ldap_delete($this->ldap, "cn={$cn},{$this->groupsDn}");
        if ($result) AuditLog::record('delete_group', $cn);
        return $result;
    }

    // =========================================================================
    // ESTADÍSTICAS
    // =========================================================================

    public function getStats(): array
    {
        $users   = $this->getAllUsers();
        $total   = count($users);
        $activos = count(array_filter($users, fn($u) => $u['activo']));

        return [
            'total'     => $total,
            'activos'   => $activos,
            'inactivos' => $total - $activos,
        ];
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function parseEntry(array $entry): array
    {
        $shadowExpire = $entry['shadowexpire'][0] ?? '-1';
        $memberOf     = [];

        if (isset($entry['memberof'])) {
            for ($j = 0; $j < $entry['memberof']['count']; $j++) {
                if (preg_match('/^cn=([^,]+)/i', $entry['memberof'][$j], $m)) {
                    $memberOf[] = $m[1];
                }
            }
        }

        return [
            'uid'             => $entry['uid'][0] ?? '',
            'nombre'          => $entry['givenname'][0] ?? '',
            'apellidos'       => $entry['sn'][0] ?? '',
            'nombre_completo' => $entry['cn'][0] ?? '',
            'mail'            => $entry['mail'][0] ?? '',
            'activo'          => ($shadowExpire === '-1' || (int) $shadowExpire > 0),
            'grupos'          => $memberOf,
        ];
    }

    /**
     * Borra el atributo operacional pwdReset del usuario vía bind de admin.
     * Necesario porque OpenLDAP 2.5 no permite bind cuando pwdReset:TRUE,
     * por lo que el cambio de contraseña forzado debe hacerse con admin.
     */

    /**
     * Exporta usuarios y grupos a formato LDIF estándar.
     * Compatible con cualquier servidor OpenLDAP.
     * Las contraseñas se incluyen hasheadas (SSHA) para permitir migración completa.
     */
    public function exportToLdif(): string
    {
        $lines = [];
        $lines[] = "# Exportación LDIF — " . date('Y-m-d H:i:s');
        $lines[] = "# Servidor: " . config('ldap.connections.default.base_dn');
        $lines[] = "# Generado por Gestor centralizado de usuarios LDAP";
        $lines[] = "";

        // ── Cabecera de la base ───────────────────────────────────────────────
        $lines[] = "dn: {$this->baseDn}";
        $lines[] = "objectClass: top";
        $lines[] = "objectClass: dcObject";
        $lines[] = "objectClass: organization";
        $lines[] = "dc: " . explode(',', $this->baseDn)[0];
        $lines[] = "o: IES Pacifico";
        $lines[] = "";

        // ── Usuarios ──────────────────────────────────────────────────────────
        $lines[] = "dn: {$this->usersDn}";
        $lines[] = "objectClass: top";
        $lines[] = "objectClass: organizationalUnit";
        $lines[] = "ou: users";
        $lines[] = "";

        $attrs   = ['objectClass', 'uid', 'cn', 'givenName', 'sn', 'mail',
                    'userPassword', 'shadowExpire'];
        $result  = ldap_search($this->ldap, $this->usersDn,
                               '(objectClass=inetOrgPerson)', $attrs);
        $entries = ldap_get_entries($this->ldap, $result);

        for ($i = 0; $i < $entries['count']; $i++) {
            $e = $entries[$i];
            $lines[] = "dn: {$e['dn']}";

            $multiAttrs = ['objectClass', 'uid', 'cn', 'givenName', 'sn',
                           'mail', 'userPassword', 'shadowExpire'];
            foreach ($multiAttrs as $attr) {
                $key = strtolower($attr);
                if (!isset($e[$key])) continue;
                $count = is_array($e[$key]) ? $e[$key]['count'] : 1;
                for ($j = 0; $j < $count; $j++) {
                    $val = $e[$key][$j];
                    $lines[] = $this->ldifLine($attr, $val);
                }
            }
            $lines[] = "";
        }

        // ── Grupos ────────────────────────────────────────────────────────────
        $lines[] = "dn: {$this->groupsDn}";
        $lines[] = "objectClass: top";
        $lines[] = "objectClass: organizationalUnit";
        $lines[] = "ou: groups";
        $lines[] = "";

        $gResult  = ldap_search($this->ldap, $this->groupsDn,
                                '(objectClass=groupOfNames)',
                                ['objectClass', 'cn', 'description', 'member']);
        $gEntries = ldap_get_entries($this->ldap, $gResult);

        for ($i = 0; $i < $gEntries['count']; $i++) {
            $e = $gEntries[$i];
            $lines[] = "dn: {$e['dn']}";
            foreach (['objectClass', 'cn', 'description', 'member'] as $attr) {
                $key = strtolower($attr);
                if (!isset($e[$key])) continue;
                $count = $e[$key]['count'];
                for ($j = 0; $j < $count; $j++) {
                    $lines[] = $this->ldifLine($attr, $e[$key][$j]);
                }
            }
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    /**
     * Formatea una línea LDIF: base64 si contiene caracteres especiales.
     * Aplica line-folding a 76 caracteres según RFC 2849.
     */
    private function ldifLine(string $attr, string $value): string
    {
        // Necesita base64 si tiene caracteres no ASCII o empieza con espacio/colon
        $needsB64 = preg_match('/[^\x09\x0a\x0d\x20-\x7e]/', $value)
                 || str_starts_with($value, ' ')
                 || str_starts_with($value, ':')
                 || str_starts_with($value, '<');

        if ($needsB64) {
            $line = "{$attr}:: " . base64_encode($value);
        } else {
            $line = "{$attr}: {$value}";
        }

        // RFC 2849: fold lines > 76 chars (continuación con un espacio)
        if (strlen($line) <= 76) return $line;

        $folded = substr($line, 0, 76);
        $rest   = substr($line, 76);
        while ($rest !== '') {
            $folded .= "\n " . substr($rest, 0, 75);
            $rest    = substr($rest, 75);
        }
        return $folded;
    }

    public function clearPwdReset(string $uid): void
    {
        $dn = "uid={$uid},{$this->usersDn}";
        @ldap_modify_batch($this->ldap, $dn, [[
            'attrib'  => 'pwdReset',
            'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
        ]]);
    }


    /**
     * Aplica StartTLS a una conexión LDAP si LDAP_TLS=true en .env.
     * Usar en todos los puntos de conexión antes del bind.
     * En despliegues single-servidor puede dejarse desactivado.
     */
    private static function applyTls(\LDAP\Connection $conn): void
    {
        if (!config('ldap.tls', false)) {
            return;
        }

        $caFile = config('ldap.tls_ca', '/etc/ldap/tls/ca.crt');
        ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $caFile);
        ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);

        if (!@ldap_start_tls($conn)) {
            throw new \RuntimeException('StartTLS fallido: ' . ldap_error($conn));
        }
    }

    private function hashPassword(string $password): string
    {
        $salt = random_bytes(8);
        return '{SSHA}' . base64_encode(sha1($password . $salt, true) . $salt);
    }

    // =========================================================================
    // MÉTODOS PARA RECUPERACIÓN DE CONTRASEÑA
    // =========================================================================

    /**
     * Busca un usuario LDAP por su dirección de correo electrónico.
     */
    public function getUserByMail(string $mail): ?array
    {
        $filter  = "(mail={$mail})";
        $attrs   = ['uid', 'cn', 'givenName', 'sn', 'mail', 'shadowExpire', 'memberOf'];
        $result  = ldap_search($this->ldap, $this->usersDn, $filter, $attrs);
        if (!$result) return null;

        $entries = ldap_get_entries($this->ldap, $result);
        if ($entries['count'] === 0) return null;

        return $this->parseEntry($entries[0]);
    }

    /**
     * Establece una nueva contraseña para un usuario mediante bind de admin.
     * No requiere la contraseña actual (usado en el flujo de recuperación).
     */
    public function setPassword(string $uid, string $newPassword): bool
    {
        $dn     = "uid={$uid},{$this->usersDn}";
        $modify = ['userPassword' => $this->hashPassword($newPassword)];

        if (!ldap_modify($this->ldap, $dn, $modify)) {
            Log::error("LDAP setPassword {$uid}: " . ldap_error($this->ldap));
            return false;
        }

        return true;
    }

}