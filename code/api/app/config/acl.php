<?php
//Create the ACL
$acl = new Phalcon\Acl\Adapter\Memory();

//The default action is DENY access
$acl->setDefaultAction(Phalcon\Acl::DENY);

/*
 * ROLES
 * Admin - can do anything
 * User - can do most things
 * Restricted User - read only
 * */

$acl->addRole(new Phalcon\Acl\Role(ROLE_GLOBAL));

/*
 * RESOURCES
 * for each user, specify the 'controller' and 'method' they have access to (user=>[controller=>[method,method]],...)
 * this is created in an array as we later loop over this structure to assign users to resources
 * */
$arrResources = [
    ROLE_GLOBAL =>[
      'NotesController'=>[
        'get',
        'post',
        'put',
        'delete',
      ],
    ],
];

foreach($arrResources as $arrResource){
    foreach($arrResource as $controller=>$arrMethods){
        $acl->addResource(new Phalcon\Acl\Resource($controller),$arrMethods);
    }
}

/*
 * ACCESS
 * */
foreach ($acl->getRoles() as $objRole) {
    $roleName = $objRole->getName();

    //everyone gets access to global resources
    foreach ($arrResources[ROLE_GLOBAL] as $resource => $method) {
        $acl->allow($roleName,$resource,$method);
    }

}

return $acl;
