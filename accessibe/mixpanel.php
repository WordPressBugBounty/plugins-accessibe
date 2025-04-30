<?php

class MixpanelHandler
{
    private $config;
    private $mixpanel;

    public function __construct()
    {
      $this->config = $this->getConfig();
      $this->mixpanel = Mixpanel::getInstance('d2a99cd5439edf8f95db8ad1fe2efdca', $this->config); // Assuming a Mixpanel PHP SDK is available
    }

    private function getConfig() {
      $config = [
        'host' => 'dashboard.accessibe.com',
        'events_endpoint' => '/mixpanel/proxy/track',
        'people_endpoint' => '/mixpanel/proxy/engage',
        'use_ssl' => true,
        'error_callback' => function($err) {
            if ($err) {
              error_log("Mixpanel Error: " . print_r($err, true));
            } else {
              error_log("Mixpanel Success: No error returned");
            }
        }
      ];

    
      return $config;
    }

    private function addDefaultProps($properties)
    {
      $properties['accessiBeProduct'] = 'Universal Plugin';
      $properties['eventType'] = 'server-side';
      $properties['pluginSource'] = 'WordPress';
      $properties['isReact'] = false;

      return $properties;
    }

    public function trackEvent($eventName, $properties = [])
    {
      if (!$this->mixpanel) {
        return false;
      }

      if (isset($properties['userId'])) {
        $properties['$user_id'] = $properties['userId'];
        $properties['distinct_id'] = $properties['userId'];
        unset($properties['userId']);
      } else if (!isset($properties['distinct_id']) && !isset($properties['$user_id'])) {
        $properties['distinct_id'] = $properties['primaryDomain'] ?? null;
      }
  
      if (strpos($eventName, '$') === false) {
        // Add default props only if the event is not system-defined (e.g., $identify)
        $properties = $this->addDefaultProps($properties);
      }

      try {
        $this->mixpanel->track($eventName, $properties);
      } catch (Exception $e) {
        error_log("Mixpanel tracking error: " . $e->getMessage());
      }
    }

    public function isInitialized()
    {
      return $this->mixpanel !== null;
    }

    public function identifyUser($userId, $uuid) 
    {
        if (!$this->mixpanel) {
          return false;
        }

        $properties = [
            '$device_id' => $uuid,
            'userId' => $userId
        ];

        $this->trackEvent('userIdentified', $properties);
    }
}