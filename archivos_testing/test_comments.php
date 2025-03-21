<?php
use Symfony\Component\HttpFoundation\Response;

/**
 * Este es un comentario de documentación
 * use App\Service\OldService;  // Este servicio no se usa
 */

class TestController {
    /*
    private LogService $logService;
    use App\Service\DeprecatedService;
    */
    
    private UserService $userService;
    
    // require_once 'old_library.php';
    public function indexAction() {
        /* Include deprecated JS
        <script src="jquery.old.js"></script>
        */
        return new Response('<script src="app.js"></script>');
    }
}