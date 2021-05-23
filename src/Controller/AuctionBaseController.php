<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Auth\DefaultPasswordHasher; // added.
use Cake\Event\Event; // added.

class AuctionBaseController extends AppController
{

  public function initialize(): void
  {
      parent::initialize();
      // 各種コンポーネントのロード
      $this->loadComponent('RequestHandler');
      $this->loadComponent('Flash');
      $this->loadComponent('Auth', [
        'authorize' => ['Controller'],
        'authenticate' => [
          'Form' => [
            'fields' => [
              'username' => 'username',
              'password' => 'password'
            ]
          ]
        ],
        'loginRedirect' => [
          'controller' => 'Users',
          'action' => 'login'
        ],
        'logoutRedirect' =>[
          'controller' => 'Users',
          'action' => 'login'
        ],
        'authError' => 'ログインしてください。',
      ]);
  }

  // 認証時のロールのチェック
  public function isAuthorized($user)
  {
      // 管理者はすべての操作にアクセスできます
      if (isset($user['role']) && $user['role'] === 'admin') {
          return true;
      }

      // デフォルトは拒否
      return false;
  }

  // ログイン処理
  function login(){
    // post時の処理
    if($this->request->isPost()) {
      $user = $this->Auth->identify();
      // Authのidentifyをユーザーに設定
      if(!empty($user)){
        $this->Auth->setUser($user);
        return $this->redirect($this->Auth->redirectUrl());
      }
      $this->Flash->error('ユーザー名かパスワードが間違っています。');
    }
  }

  // ログアウト処理
  public function logout(){
    // セッションを破棄
    $this->request->session()->destroy();
    return $this->redirect($this->Auth->logout());
  }

  //認証を使わないページの設定
  public function beforeFilter(\Cake\Event\EventInterface $event){
    parent::beforeFilter($event);
    $this->Auth->allow([]); // 後で'add'を削除する
  }
}
