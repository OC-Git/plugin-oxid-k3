<?php

namespace ObjectCode\K3\Extend\Core;

class Session extends Session_parent
{
    /**
     * the 'oc_ock3_basketcontroller' controller requires a session
     * @return array
     */
    protected function _getRequireSessionWithParams()
    {
        return array_merge_recursive(parent::_getRequireSessionWithParams(), [
            'cl' => [
                'oc_ock3_basketcontroller' => true,
            ]
        ]);
    }
}
