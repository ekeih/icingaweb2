<?php

namespace Icinga\Monitoring\Backend;

use Icinga\Data\DatasourceInterface;
use Icinga\Exception\ProgrammingError;
use Icinga\Application\Benchmark;
use Zend_Config;

class AbstractBackend implements DatasourceInterface
{
    protected $config;

    public function __construct(Zend_Config $config = null)
    {
        if ($config === null) {
            // $config = new Zend_Config(array()); ???
        }
        $this->config = $config;
        $this->init();
    }

    protected function init()
    {
    }

    /**
     * Dummy function for fluent code
     *
     * return self
     */
    public function select()
    {
        return $this;
    }

    /**
     * Create a Query object instance for given virtual table and desired fields
     *
     * Leave fields empty to get all available properties
     *
     * @param string Virtual table name
     * @param array  Fields
     * return self
     */
    public function from($virtual_table, $fields = array())
    {
        $classname = $this->tableToClassName($virtual_table);
        if (!class_exists($classname)) {
            throw new ProgrammingError(
                sprintf(
                    'Asking for invalid virtual table %s',
                    $classname
                )
            );
        }
        $query = new $classname($this, $fields);
        return $query;
    }

    public function hasView($virtual_table)
    {
        // TODO: This is no longer enough, have to check for Query right now
        return class_exists($this->tableToClassName($virtual_table));
    }

    protected function tableToClassName($virtual_table)
    {
        return 'Icinga\\Monitoring\\View\\'
        // . $this->getName()
        // . '\\'
        . ucfirst($virtual_table)
        . 'View';
    }

    public function getName()
    {
        return preg_replace('~^.+\\\(.+?)$~', '$1', get_class($this));
    }

    public function __toString()
    {
        return $this->getName();
    }


    // UGLY temporary host fetch
    public function fetchHost($host, $fetchAll = false)
    {
        $fields = array(
            'host_name',
            'host_address',
            'host_state',
            'host_handled',
            'host_in_downtime',
            'host_acknowledged',
            'host_check_command',
            'host_last_state_change',
            'host_alias',
            'host_output',
            'host_long_output',
            'host_perfdata'
        );

        $fields = array_merge(
            $fields,
            array(
                'current_check_attempt',
                'max_check_attempts',
                'attempt',
                'last_check',
                'next_check',
                'check_type',
                'last_state_change',
                'last_hard_state_change',
                'last_hard_state',
                'last_time_up',
                'last_time_down',
                'last_time_unreachable',
                'state_type',
                'last_notification',
                'next_notification',
                'no_more_notifications',
                'notifications_enabled',
                'problem_has_been_acknowledged',
                'acknowledgement_type',
                'current_notification_number',
                'passive_checks_enabled',
                'active_checks_enabled',
                'event_handler_enabled',
                'flap_detection_enabled',
                'is_flapping',
                'percent_state_change',
                'latency',
                'execution_time',
                'scheduled_downtime_depth',
                'failure_prediction_enabled',
                'process_performance_data',
                'obsess_over_host',
                'modified_host_attributes',
                'event_handler',
                'check_command',
                'normal_check_interval',
                'retry_check_interval',
                'check_timeperiod_object_id'
            )
        );

        $select = $this->select()
            ->from('status', $fields)
            ->where('host_name', $host);
        return $select->fetchRow();
    }

    // UGLY temporary service fetch
    public function fetchService($host, $service)
    {
        Benchmark::measure('Preparing service select');
        $select = $this->select()
            ->from(
                'status',
                array(

                    'host_name',
                    'host_state',
                    'host_check_command',
                    'host_last_state_change',
                    'service_description',
                    'service_state',
                    'service_acknowledged',
                    'service_handled',
                    'service_output',
                    'service_long_output',
                    'service_perfdata',
                    // '_host_satellite',
                    'service_check_command',
                    'service_last_state_change',
                    'service_last_check',
                    'service_next_check',
                    'service_check_execution_time',
                    'service_check_latency',
                    //            'service_
                )
            )
            ->where('host_name', $host)
            ->where('service_description', $service);
        // Benchmark::measure((string) $select->getQuery());
        Benchmark::measure('Prepared service select');

        return $select->fetchRow();
        $object = \Icinga\Objects\Service::fromBackend(
            $this->select()
                ->from(
                    'status',
                    array(

                        'host_name',
                        'host_state',
                        'host_check_command',
                        'host_last_state_change',
                        'service_description',
                        'service_state',
                        'service_acknowledged',
                        'service_handled',
                        'service_output',
                        'service_long_output',
                        'service_perfdata',
                        // '_host_satellite',
                        'service_check_command',
                        'service_last_state_change',
                        'service_last_check',
                        'service_next_check',
                        'service_check_execution_time',
                        'service_check_latency',
                        //            'service_
                    )
                )
                ->where('host_name', $host)
                ->where('service_description', $service)
                ->fetchRow()
        );
        // $object->customvars = $this->fetchCustomvars($host, $service);
        return $object;
    }


}
