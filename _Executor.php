<?php

namespace NGFramer\NGFramerPHPSQLServices;

use Exception;
use NGFramer\NGFramerPHPDbServices\Database;

trait _Executor
{
    // Variables to store query JSON data related to query.
    private static ?Database $database = null;


    // Function to execute the query.
    private static function connect(): void
    {
        // Only get the instance again if the instance is empty.
        if (empty(self::$database)) {
            // Create a new instance of the Database class for executing the query.
            self::$database = Database::getInstance();
        }
    }

    // Function to execute the query using prepared statement.
    /**
     * @throws Exception
     */
    private function preparedExecution(): int|array|bool
    {
        // Firstly, connect to the database.
        $this->connect();
        // Get all the following details queryBuilder and queryBindValueBuilder.
        $query = $this->query = $this->buildQuery();
        $bindValues = $this->bindValues = $this->buildBindValues();

        // Try initiating the transaction and record its status.
        $beginTransactionStatus = self::$database->beginTransaction();
        if (!$beginTransactionStatus) {
            throw new Exception("Unable to initiate the transaction.");
        }

        // Prepare the query and bind the parameters.
        self::$database->prepare($query)->bindValues($bindValues);

        // Now, based on the action, commit or rollback the transaction.
        return $this->runTransaction();
    }


    // Function to execute the query using direct statement.

    /**
     * @throws Exception
     */
    private function directExecution(): int|array|bool
    {
        // Get all the following details queryBuilder.
        $query = $this->query = $this->buildQuery();

        // Try initiating the transaction and record its status.
        $beginTransactionStatus = self::$database->beginTransaction();
        if (!$beginTransactionStatus) {
            throw new Exception("Unable to initiate the transaction.");
        }

        // Now, based on the action, commit or rollback the transaction.
        return $this->runTransaction();
    }


    // Function to run the transaction based on the action.

    /**
     * @throws Exception
     */
    public function runTransaction(): int|array|bool
    {
        // Get the query and action from the queryJson.
        $query = $this->query;
        $action = $this->action;

        // Now, execute the query, and check, if the execution is successful, commit it, else rollback the transaction.
        try {
            // Execute the query using prepared or direct execution method based on what has been asked.
            // For the prepared execution method, use the already prepared statement, that has already been bind, just execute it.
            if (!$this->isGoDirect()) {
                self::$database->execute();
            } else {
                self::$database->execute($query);
            }
            self::$database->commit();
        } catch (Exception $e) {
            error_log($e);
            self::$database->rollBack();
            throw new Exception("Error executing the query.");
        }

        // Now, based on the action, commit or rollback the transaction.
        if ($action == 'insertData') {
            // Find the last inserted ID.
            return self::$database->lastInsertId();
        } elseif ($action == 'updateData' or $action == 'deleteData') {
            // Find the number of rows affected.
            return self::$database->rowCount();
        } elseif ($action == 'selectData') {
            // Fetch all the results.
            return self::$database->fetchAll();
        } elseif (in_array($action, ['alterTable', 'alterView', 'createTable', 'createView', 'dropTable', 'dropView', 'renameTable', 'renameView', 'truncateTable'])) {
            // For all the other data definition based actions, return true.
            return true;
        }
        error_log("Error on the following query " . $this->query . "with parameters " . json_encode($this->bindValues) . ".");
        throw new Exception("Invalid action" . $this->getAction() . ". Please check the action in the query.");
    }


}