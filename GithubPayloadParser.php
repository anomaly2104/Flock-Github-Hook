<?php
abstract class GithubActionPR {
    const Opened = 0;
    const ReOpened = 1;
    const Closed = 2;
    const Merged = 3;
    const Others = 4;
}

class GithubPayloadParser {
    private $payload;

    function __construct($payload) {
        $this->payload = $payload;
    }

    private function pullRequest() {
        return $this->payload->pull_request;
    }

    public function action() {
        $githubActionsMapper = array(
            "opened" => GithubActionPR::Opened,
            "closed" => GithubActionPR::Closed,
            "reopened" => GithubActionPR::ReOpened);
        $actionString = $this->payload->action;
        if(!isset($githubActionsMapper[$actionString])) {
            return GithubActionPR::Others;
        }
        $githubAction = $githubActionsMapper[$actionString];
        $isMerged = $this->pullRequest()->merged;
        if($githubAction == GithubActionPR::Closed && $isMerged) {
            return GithubActionPR::Merged;
        }
        return $githubAction;
    }

    public function actionString() {
        $action = $this->action();
        $mapper = array(
            GithubActionPR::Merged => "merged",
            GithubActionPR::Opened => "opened",
            GithubActionPR::Closed => "closed",
            GithubActionPR::ReOpened => "re-opened");
        return $mapper[$action];
    }

    public function pullRequestTitle() {
        return $this->pullRequest()->title;
    }

    public function pullRequestURL() {
        return $this->pullRequest()->html_url;
    }

    public function pullRequestDisplayActor() {
        $user = $this->pullRequest()->user->login;
        $action = $this->action();
        if($action == GithubActionPR::Merged) {
            $user = $this->pullRequest()->merged_by->login;
        }
        return "@".$user;
    }
}
?>