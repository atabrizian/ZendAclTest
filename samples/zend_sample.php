<?php
require_once '../vendor/autoload.php'; //this is for composer autoload

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

$sampleHost = "localhost";
$sampleDbName = "zendacl";
$sampleUsername = "testUsername";
$samplePassword = "testPassword";

define('ACTION_ALL', 'all');
define('ACTION_CREATE', 'create');
define('ACTION_READ', 'read');
define('ACTION_UPDATE', 'update');
define('ACTION_DELETE', 'delete');

define('DB_USER_ROLES', 'users_roles');
define('DB_ROLES_RESOURCES', 'roles_resources');
define('DB_RESOURCES', 'resources');
define('DB_ROLES', 'roles');

$allPossibleActions = array(
    ACTION_CREATE,
    ACTION_READ,
    ACTION_UPDATE,
    ACTION_DELETE
);

$pdo = new PDO("mysql:host={$sampleHost};dbname={$sampleDbName};", $sampleUsername, $samplePassword);
$zendAcl = new Acl();
$zendAcl->addResource(new Resource('public'));
$zendAcl->addResource(new Resource('secret'));
$zendAcl->addResource(new Resource('user_can_only_read'));
$zendAcl->addResource(new Resource('user_can_not_delete'));
$sampleUserId = 1;

$ph = $pdo->prepare("SELECT `role_id` FROM `" . DB_USER_ROLES . "` WHERE `user_id` = :user_id;");
if ($ph->execute(array(':user_id' => $sampleUserId))) {
    $roles = $ph->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_COLUMN, 'role_id');
    $ph->closeCursor();
    if ($roles !== false) {
        foreach ($roles as $role) {
            $zendAcl->addRole(new Role($role));
        }

        foreach ($roles as $role) {
            $ph = $pdo->prepare("SELECT * FROM `" . DB_ROLES_RESOURCES . "` WHERE `role_id` = :role_id");

            if ($ph->execute(array(':role_id' => $role))) {
                $rules = $ph->fetchAll(PDO::FETCH_ASSOC);
                $ph->closeCursor();

                if ($rules !== false) {
                    foreach ($rules as $rule) {
                        $zendAcl->setRule(
                            Acl::OP_ADD,
                            strtoupper('type_' . $rule['privilege']),
                            $role,
                            $rule['resource'],
                            $rule['action']
                        );
                    }
                } else {
                    throw new Exception("Rule selection failed!");
                }
            } else {
                throw new Exception("Rule selection failed!");
            }
        }
    } else {
        throw new Exception("Role selection failed!");
    }
} else {
    throw new Exception("Role selection failed!");
}

//print_r($zendAcl);
/**
 * First of all test User.
 * User is just a member of role 'User'
 */

echo "Checking ACL for user 'user':<br>";

echo "His roles are:<br>";
echo '<pre>';
print_r($zendAcl->getRoles());
echo '</pre>';

echo '<br><hr><br>';

echo "Checking 'secret':<br>";
if ($zendAcl->isAllowed(2, 'secret', ACTION_CREATE) and $zendAcl->isAllowed(
        2,
        'secret',
        ACTION_READ
    ) and $zendAcl->isAllowed(2, 'secret', ACTION_UPDATE) and $zendAcl->isAllowed(
        2,
        'secret',
        ACTION_DELETE
    )
) {
    echo "Yes, 'all' action is allowed.<br>";
} else {
    echo "No, 'all' action is not allowed.<br>";
}

echo '<br><hr><br>';

echo "Checking 'public':<br>";
if ($zendAcl->isAllowed(2, 'public', ACTION_CREATE) and $zendAcl->isAllowed(
        2,
        'public',
        ACTION_READ
    ) and $zendAcl->isAllowed(2, 'public', ACTION_UPDATE) and $zendAcl->isAllowed(
        2,
        'public',
        ACTION_DELETE
    )
) {
    echo "Yes, 'all' action is allowed.<br>";
} else {
    echo "No, 'all' action is not allowed.<br>";
}

echo '<br><hr><br>';

echo "Checking 'user_can_only_read':<br>";

if ($zendAcl->isAllowed(2, 'user_can_only_read', ACTION_CREATE) and $zendAcl->isAllowed(
        2,
        'user_can_only_read',
        ACTION_READ
    ) and $zendAcl->isAllowed(2, 'user_can_only_read', ACTION_UPDATE) and $zendAcl->isAllowed(
        2,
        'user_can_only_read',
        ACTION_DELETE
    )
) {
    echo "Yes, 'all' action is allowed.<br>";
} else {
    echo "No, 'all' action is not allowed.<br>";
}

if ($zendAcl->isAllowed(2, 'user_can_only_read', ACTION_READ)) {
    echo "Yes, read is allowed.<br>";
} else {
    echo "No, read is not allowed.<br>";
}

echo '<br><hr><br>';

echo "Checking 'user_can_not_delete':<br>";

if ($zendAcl->isAllowed(2, 'user_can_not_delete', ACTION_CREATE) and $zendAcl->isAllowed(
        2,
        'user_can_not_delete',
        ACTION_READ
    ) and $zendAcl->isAllowed(2, 'user_can_not_delete', ACTION_UPDATE) and $zendAcl->isAllowed(
        2,
        'user_can_not_delete',
        ACTION_DELETE
    )
) {
    echo "Yes, 'all' action is allowed.<br>";
} else {
    echo "No, 'all' action is not allowed.<br>";
}

if ($zendAcl->isAllowed(2, 'user_can_not_delete', ACTION_READ)) {
    echo "Yes, read is allowed.<br>";
} else {
    echo "No, read is not allowed.<br>";
}

if ($zendAcl->isAllowed(2, 'user_can_not_delete', ACTION_DELETE)) {
    echo "Yes, delete is allowed.<br>";
} else {
    echo "No, delete is not allowed.<br>";
}

echo '<br><hr><br>';