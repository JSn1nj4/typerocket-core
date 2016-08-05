<?php
namespace TypeRocket\Controllers;

use TypeRocket\Models\UsersModel;

class UsersController extends Controller
{

    /**
     * Update user by ID
     *
     * @param null $id
     *
     * @return mixed|void
     */
    public function update( $id = null )
    {
        $user   = new UsersModel();
        $errors = $user->findById( $id )->update( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'User not updated', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'User updated', 'success' );
            $this->response->setData('resourceId', $user->getId());
        }
    }

    /**
     * Create user
     */
    public function create()
    {
        $user   = new UsersModel();
        $errors = $user->create( $this->request->getFields() )->getErrors();

        if ( ! empty ( $errors )) {
            $this->response->flashNext( 'User not created', 'error' );
            $this->response->setError( 'model', $errors );
        } else {
            $this->response->flashNext( 'User created', 'success' );
            $this->response->setStatus(201);
            $this->response->setData('resourceId', $user->getId());
        }
    }

}
