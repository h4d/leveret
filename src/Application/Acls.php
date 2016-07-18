<?php


namespace H4D\Leveret\Application;


class Acls
{
    /**
     * @var AclInterface[]
     */
    protected $controllerAcls;
    /**
     * @var AclInterface[]
     */
    protected $routeAcls;

    /**
     * Acls constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param AclInterface $acl
     * @param string $controllerName
     * @param array $applyToActions
     * @param array $excludedActions
     *
     * @return $this
     */
    public function addAclForController(AclInterface $acl,
                                             $controllerName,
                                             $applyToActions = ['*'],
                                             $excludedActions = [])
    {
        $this->controllerAcls[$controllerName][] = ['acl' => $acl,
                                                    'applyTo' => $applyToActions,
                                                    'exclude' => $excludedActions];

        return $this;
    }

    /**
     * @param string $controllerName
     * @param string $action
     *
     * @return AclInterface[]
     */
    public function getAclsForController($controllerName, $action)
    {
        $acls = [];
        if (array_key_exists($controllerName, $this->controllerAcls))
        {
            $auxAcls = $this->controllerAcls[$controllerName];
            foreach ($auxAcls as $aclData)
            {
                if (!in_array($action, $aclData['exclude']))
                {
                    if (in_array('*', $aclData['applyTo']) || in_array($action, $aclData['applyTo']))
                    {
                        $acls[] = $aclData['acl'];
                    }
                }

            }
        }

        return $acls;
    }

    /**
     * @param AclInterface $acl
     * @param string $routeName
     *
     * @return $this
     */
    public function addAclForRoute(AclInterface $acl, $routeName)
    {
        $this->routeAcls[$routeName][] = ['acl' => $acl];

        return $this;
    }

    /**
     * @param string $routeName
     *
     * @return AclInterface[]
     */
    public function getAclsForRoute($routeName)
    {
        $acls = [];
        if (array_key_exists($routeName, $this->routeAcls))
        {
            $acls = $this->routeAcls[$routeName];
        }

        return $acls;
    }
}
