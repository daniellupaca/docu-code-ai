<?php
require_once '../services/aiservice.php';
require_once '../services/umlservice.php';

class CodeFacade {
    private $aiService;
    private $umlService;

    public function __construct() {
        $this->aiService = new AIService();
        $this->umlService = new UMLService();
    }

    public function processCode($code) {
        $comments = $this->aiService->generateComments($code);
        $uml = $this->umlService->generateUML($code);
        return ['comments' => $comments, 'uml' => $uml];
    }
}
?>
