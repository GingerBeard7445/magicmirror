<?php
class Google {
    static function SetService()
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';
        $client = new Google_Client();
        $client->setApplicationName('magicmirror');
        $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
        $client->setAuthConfig('/var/login/google-calendar-api.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Set access tokenRedirect the user to the OAuth consent screen
        // Set the access token
        if (isset($_GET['code']) && !isset($_SESSION['access_token'])) {
            $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $_SESSION['access_token'] = $client->getAccessToken();
        } else if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $client->setAccessToken($_SESSION['access_token']);
            $accessToken = $client->getAccessToken();
        }
        
        if (!isset($accessToken)) {
            $authUrl = $client->createAuthUrl();
            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        } else {
            $client->fetchAccessTokenWithAuthCode($accessToken['access_token']);
            $_SESSION['access_token'] = $client->getAccessToken();
            if ($_SERVER['HTTP_HOST'] != "www.magicmirror-pth.com") {
                header('Location: ' . filter_var('http://www.magicmirror-pth.com', FILTER_SANITIZE_URL));
            }
        }

        $service = new Google_Service_Calendar($client);
        return $service;
    }
    /**
     * Gets list of calendars in a Google calendar
     */
    static function GetCalendars() 
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';
        $service = self::SetService();
        $calendarList = $service->calendarList->listCalendarList();
        $calendars = array();
        foreach ($calendarList->getItems() as $calendarListEntry) {
            if ($calendarListEntry->getSummary() <> 'Gmail') {
                $calendars[] = array(
                    'id' => $calendarListEntry->getID(),
                    'summary' => $calendarListEntry->getSummary(),
                    'backgroundColor' => $calendarListEntry->getBackgroundColor()
                );
            }
        }
        return $calendars;
    }
    /**
     * Gets list of events in a Google calendar
     * 
     * calendarId   ID of calendar event will be added to (See calendar table in the database)
     */
    static function GetEvents($calendar, $timeMin, $timeMax) 
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';
        $service = self::SetService();

        $params = array(
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => true
        );

        $eventList = array();
        $events = $service->events->listEvents($calendar['id'], $params);
        
        while(true) {
            foreach ($events->getItems() as $event) {
                $offset = 0;

                if ($event->getStart()->getTimezone()) {
                    $timezone = new DateTimeZone($event->getStart()->getTimezone());
                    $dateTime = new DateTime('now', $timezone);
                    $offset = $timezone->getOffset($dateTime);
                }
                
                $startDate = $event->getStart()->getDate();
                if (!isset($startDate)) {
                    $startDate = $event->getStart()->getDateTime();
                    $startDate = substr($startDate, 0, strlen($startDate) - 1);
                    $startDate = date('Y-m-d H:i', strtotime($startDate) + $offset);
                }
                
                $endDate = $event->getEnd()->getDate();
                if (!isset($endDate)) {
                    $endDate = $event->getEnd()->getDateTime();
                    $endDate = substr($endDate, 0, strlen($endDate) - 1);
                    $endDate = date('Y-m-d H:i', strtotime($endDate) + $offset);
                }

                $dayOf = date('Y-m-d', strtotime($startDate));

                $eventList[] = array(
                    'calendar' => $calendar['id'],
                    'summary' => $event->getSummary(),
                    'dayOf' => $dayOf,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'timezone' => $event->getStart()->getTimezone(),
                    'backgroundColor' => $calendar['backgroundColor']
                );
            }
            $pageToken = $events->getNextPageToken();
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
                $events = $service->events->listEvents($calendarId, $params);
            } else {
                break;
            }
        }

        return $eventList;
    }
    /**
     * Creates a Google calendar event
     * 
     * calendarId   ID of calendar event will be added to (See calendar table in the database)
     * summary      Events title
     * location     Events location
     * description  Event details
     * start        Start date/time of event (Y-m-d H:i)
     * end          End date/time of event (Y-m-d H:i)
     * attendees    Array of emails | array('email1@nothum.com', 'email2@nothum.com')
     * timezone     Number between -12 and 14 representing the timezone for the event, -6 (CMT) is the default value
     */
    static function CreateEvent($calendarId, 
    $summary, $location, $description,
    $startDate, $endDate, 
    $timezone = 'America/Chicago', 
    $attendees = NULL, 
    $startTime = NULL, $endTime = NULL) 
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';
        $service = self::SetService();

        // $event = new Google_Service_Calendar_Event(array(
        //     'summary' => 'Test Event',
        //     'description' => 'Test Event',
        //     'start' => array(
        //       'dateTime' => '2024-01-02T09:00:00-07:00'
        //     ),
        //     'end' => array(
        //       'dateTime' => '2024-01-02T11:00:00-07:00'
        //     ),
        //     'attendees' => array(
        //         array('email' => 'philip.heppeler@nothum.com'),
        //         array('email' => 'jim.blakey@nothum.com'),
        //     ),
        // ));

        if ($summary == '') {
            throw new Exception("Summary can not be blank");
        }
        if ($location == '') {
            throw new Exception("Location can not be blank");
        }
        if ($description == '') {
            throw new Exception("Description can not be blank");
        }
        if ($calendarId == '') {
            throw new Exception("CalendarID can not be blank");
        }

        if ($startDate == '') {
            throw new Exception("Start date can not be blank");
        }
        if ($endDate == '') {
            throw new Exception("End date can not be blank");
        }
        if (!strtotime($startDate)) {
            throw new Exception("Start time must be a valid datetime");
        }
        if (!strtotime($endDate)) {
            throw new Exception("End time must be a valid datetime");
        }
        
        if ($startTime <> '' && $endTime == '') {
            throw new Exception("End time must be set if start time is set");
        }
        if ($endTime <> '' && $startTime == '') {
            throw new Exception("Start time must be set if end time is set");
        }
        if ($startTime <> '' && !strtotime($startTime)) {
            throw new Exception("Start time must be a valid datetime");
        }
        if ($endTime <> '' && !strtotime($endTime)) {
            throw new Exception("End time must be a valid datetime");
        }
        
        if (isset($attendees)) {
            if (!is_array($attendees)) {
                throw new Exception("Attendees must be a single dimension array comprised of emails");
            }
            
            foreach($attendees as $a) {
                if (is_array($a)) {
                    throw new Exception("Each item in attendees must be a string value");
                }
            }
        }

        $eventDetails = array();
        $eventDetails['summary'] = urldecode($summary);
        $eventDetails['location'] = urldecode($location);
        $eventDetails['description'] = urldecode($description);
        $eventDetails['start']['timeZone'] = $timezone;
        $eventDetails['end']['timeZone'] = $timezone;
        if ($startTime <> '') {
            $eventDetails['start']['dateTime'] = date('Y-m-d', strtotime($startDate)) . 'T' . date('H:i:00', strtotime($startTime));
        } else {
            $eventDetails['start']['date'] = date('Y-m-d', strtotime($startDate));
        }
        if ($endTime <> '') {
            $eventDetails['end']['dateTime'] = date('Y-m-d', strtotime($endDate)) . 'T' . date('H:i:00', strtotime($endTime));
        } else {
            $eventDetails['end']['date'] = date('Y-m-d', strtotime('+1 day', strtotime($endDate)));
        }
        
        if (isset($attendees)) {
            $attendessArray = array();
            foreach($attendees as $a) {
                $attendessArray[] = array('email' => $a);
            }
            $eventDetails['attendees'] = $attendessArray;
        }

        $event = new Google_Service_Calendar_Event($eventDetails);
        $event = $service->events->insert($calendarId, $event);
        return $event->id;
    }
    /**
     * Updates a Google calendar event
     * 
     * calendarId   ID of calendar event will be added to (See googlecalendar table in the database)
     * eventId      ID of event (See googlecalendarevent table in the database)
     * summary      Events title
     * location     Events location
     * description  Event details
     * start        Start date/time of event (Y-m-d H:i)
     * end          End date/time of event (Y-m-d H:i)
     * attendees    Array of emails | array('email1@nothum.com', 'email2@nothum.com')
     * timezone     Number between -12 and 14 representing the timezone for the event, -6 (CMT) is the default value
     */
    static function UpdateEvent($calendarId, $eventId,
    $summary = NULL, $location = NULL, $description = NULL, 
    $startDate = NULL, $endDate = NULL, $attendees = NULL, 
    $timezone = 'America/Chicago', $startTime = NULL, $endTime = NULL) 
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';
        $service = self::SetService();

        if ($startDate <> '' && !strtotime($startDate)) {
            throw new Exception("Invalid start date.");
        }
        if ($endDate <> '' && !strtotime($endDate)) {
            throw new Exception("Invalid end date.");
        }

        if ($startTime <> '' && $endTime == '') {
            throw new Exception("End time must be set if start time is set");
        }
        if ($endTime <> '' && $startTime == '') {
            throw new Exception("Start time must be set if end time is set");
        }
        if ($startTime <> '' && !strtotime($startTime)) {
            throw new Exception("Invalid start time.");
        }
        if ($endTime <> '' && !strtotime($endTime)) {
            throw new Exception("Invalid end time.");
        }

        if (isset($attendees)) {
            if (!is_array($attendees)) {
                throw new Exception("Attendees must be a single dimension array comprised of emails");
            }
            
            foreach($attendees as $a) {
                if (is_array($a)) {
                    throw new Exception("Each item in attendees must be a string value");
                }
            }
        }

        $event = $service->events->get($calendarId, $eventId);

        if ($summary <> '') {
            $event->setSummary($summary);
        }
        if ($location <> '') {
            $event->setLocation($location);
        }
        if ($description <> '') {
            $event->setDescription($description);
        }

        if (isset($attendees)) {
            $attendessArray = array();
            foreach($attendees as $a) {
                $attendessArray[] = array('email' => $a);
            }
            $event->setAttendees($attendessArray);
        }
        
        if ($startDate <> '') {
            $event['start']['timeZone'] = $timezone;
            $event['start']['date'] = NULL;
            $event['start']['dateTime'] = NULL;
            if ($startTime <> '') {
                $event['start']['dateTime'] = date('Y-m-d', strtotime($startDate)) . 'T' . date('H:i:00', strtotime($startTime));
            } else {
                $event['start']['date'] = date('Y-m-d', strtotime($startDate));
            }
        }
        if ($endDate <> '') {
            $event['end']['timeZone'] = $timezone;
            $event['end']['date'] = NULL;
            $event['end']['dateTime'] = NULL;
            if ($startTime <> '') {
                $event['end']['dateTime'] = date('Y-m-d', strtotime($endDate)) . 'T' . date('H:i:00', strtotime($endTime));
            } else {
                $event['end']['date'] = date('Y-m-d', strtotime('+1 day', strtotime($endDate)));
            }
        }

        // Reactivate the event if it was deleted manually
        $event->setStatus('confirmed');

        $service->events->update($calendarId, $eventId, $event);
    }
    /**
     * Deletes a Google calendar event
     * 
     * calendarId   ID of calendar event will be added to (See googlecalendar table in the database)
     * eventId      ID of event (See googlecalendarevent table in the database)
     */
    static function DeleteEvent($calendarId, $eventId)
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';

        if ($calendarId == '') {
            throw new Exception("CalendarID can not be blank");
        }
        if ($eventId == '') {
            throw new Exception("EventID can not be blank");
        }

        $service = self::SetService();
        $event = $service->events->delete($calendarId, $eventId);
    }
    /**
     * Change an events calendar
     */
    static function ChangeCalendar($originalCalendarId, $eventId, $newCalendarId)
    {
        require_once 'thirdparty/googleapi/vendor/autoload.php';

        if ($originalCalendarId == '') {
            throw new Exception("OriginalCalendarID can not be blank");
        }
        if ($eventId == '') {
            throw new Exception("EventID can not be blank");
        }
        if ($originalCalendarId == '') {
            throw new Exception("NewCalendarId can not be blank");
        }

        $service = self::SetService();
        $event = $service->events->move($originalCalendarId, $eventId, $newCalendarId);
    }
}
?>