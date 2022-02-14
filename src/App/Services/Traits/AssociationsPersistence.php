<?php

namespace Kanata\Services\Traits;

use Error;
use Exception;
use Kanata\Models\WsAssociation;

trait AssociationsPersistence
{
    /**
     * Associate a user id to a fd.
     *
     * @param int $fd
     * @param int $userId
     * @return void
     */
    public function assoc(int $fd, int $userId): void
    {
        $this->disassoc($fd);
        try {
            WsAssociation::getInstance()->createRecord([
                'fd' => $fd,
                'user_id' => $userId,
            ]);
        } catch(Exception|Error $e) {
            // --
        }
    }

    /**
     * Disassociate a user from a fd.
     *
     * @param int $fd
     * @return void
     */
    public function disassoc(int $fd): void
    {
        try {
            WsAssociation::getInstance()->where('fd', '=', $fd)->delete();
        } catch (Exception|Error $e) {
            // --
        }
    }

    /**
     * Get user-id for a fd.
     *
     * @param int $fd
     * @return int
     */
    public function getAssoc(int $fd): int
    {
        return WsAssociation::getInstance()->where('fd', '=', $fd)->find()->user_id;
    }

    /**
     * Retrieve all associations.
     *
     * @return array Format:
     */
    public function getAllAssocs(): array
    {
        try {
            $associations = WsAssociation::getInstance()->findAll()->asArray();
        } catch (Exception|Error $e) {
            return [];
        }

        if (empty($associations)) {
            return [];
        }

        $connections = [];
        foreach ($associations as $association) {
            $connections[$association['fd']] = $association['user_id'];
        }

        return $connections;
    }
}