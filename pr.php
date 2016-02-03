<?php
function logMessage($msg) {
    error_log($msg, 3, "my-errors.log");
    error_log("\n", 3, "my-errors.log");
}

require_once "GitHub_WebHook.php";

/**
 * This config file contains URL to my Flock's webhook in variable.
 *
 * Example:
 * $flockWebHook = "https://api.flock.co/hooks/sendMessage/<token>";
 *
 * For the purpose of security, this file is not added to the git repository.
 */
require_once "config.php";

try {
    $githubHook = new GitHub_WebHook();
    $githubHook->ProcessRequest();

    $eventType = $githubHook->GetEventType();
    $repositoryFullName = $githubHook->GetFullRepositoryName();
    $payload = $githubHook->GetPayload();

    if($eventType == "pull_request") {
        logMessage('Payload '.json_encode($payload));
        $action = $payload->action;

        if($action == "opened"
        || $action == "closed"
        || $action == "reopened") {
            $pullRequest = $payload->pull_request;
            $merged = $pullRequest->merged;

            logMessage('PR: '.json_encode($pullRequest));

            $title = $pullRequest->title;
            $url = $pullRequest->html_url;

            $user = $pullRequest->user->login;

            if($merged && $action == "closed") {
                $action = "merged";
                $user = $pullRequest->merged_by->login;
            }

            $msg = "$user $action a pull request: '$title', $url";

            $ch = curl_init();

            $query = json_encode(array('text' => $msg));

            logMessage($query);
            curl_setopt($ch, CURLOPT_URL, $flockWebHook);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

            $headers= array('Content-Type: application/json');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec ($ch);

            curl_close ($ch);

            logMessage($server_output);
        }
    }

} catch(Exception $e) {
    logMessage("Error: exception occurred".$e->getMessage());
}

?>