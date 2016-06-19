<?php

final class SubmitQueueClient extends Phobject {

    private $uri;
    private $host;
    private $timeout;

    public function __construct($uri, $timeout=10) {
        $this->uri = new PhutilURI($uri);
        if (!strlen($this->uri->getDomain())) {
            throw new Exception(
                pht("SubmitQueue URI '%s' must include a valid host.", $uri));
        }
        $this->host = $this->uri->getDomain();
        $this->timeout = $timeout;
    }

    public function submitMergeRequest($remote, $diffId, $revisionId) {
        $params = array(
          'remote' => $remote,
          'diffId' => $diffId,
          'revisionId' => $revisionId,
        );
        $result = $this->callMethodSynchronous("POST", "/merge_requests", $params);
        return "whatever";
    }

    private function callMethodSynchronous($method, $api, array $params) {
        return $this->callMethod($method, $api, $params)->resolve();
    }

    private function callMethod($method, $api, array $params) {
        $req = id(clone $this->uri)->setPath('/api'.$api.'?'.http_build_query($params));
        // Always use the cURL-based HTTPSFuture, for proxy support and other
        // protocol edge cases that HTTPFuture does not support.
        $core_future = new HTTPSFuture($req);
        $core_future->addHeader('Host', "http://localhost:8080/");

        $core_future->setMethod($method);
        $core_future->setTimeout($this->timeout);

        $json_future = new SubmitQueueFuture($core_future);
        $json_future->isReady();

        return $json_future;
    }
}
