<?php
namespace TypeRocket\Http\Responders;

use \TypeRocket\Http\Request,
    \TypeRocket\Http\Response;

class CommentsResponder extends Responder {

    /**
     * Respond to comments hook
     *
     * Create proper request and run through Kernel
     *
     * @param $commentId
     */
    public function respond( $commentId ) {

        $request = new Request('comments', 'PUT', $commentId, 'update');
        $response = new Response();
        $response->blockFlash();

        $this->runKernel($request, $response);

    }

}