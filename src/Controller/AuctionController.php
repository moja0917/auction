<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Exception;
use Cake\ORM\TableRegistry;

/**
 * Auctions Controller
 *
 *
 * @method \App\Model\Entity\Auction[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AuctionController extends AuctionBaseController
{
  public $useTable = false;

  public function initialize(): void
  {
    parent::initialize();
    $this->loadComponent('Paginator');
    $this->loadModel('Users');
    $this->loadModel('Biditems');
    $this->loadModel('Bidrequests');
    $this->loadModel('Bidinfo');
    $this->loadModel('Bidmessages');
    $this->set('authuser', $this->Auth->user());
    $this->viewBuilder()->setLayout('auction');
  }

  public function index()
  {
    $auction = $this->paginate('Biditems', [
      'order' => ['endtime' => 'desc'],
      'limit' => 10]);
    $this->set(compact('auction'));
  }

  public function view($id = null)
  {
    $biditem = $this->Biditems->get($id, [
      'contain' => ['Users', 'Bidinfo', 'Bidinfo.Users']
    ]);
    if ($biditem->endtime < new \DateTime('now') and $biditem->finished == 0){
      $biditem->finished = 1;
      $this->Biditems->save($biditem);
      $bidinfo = $this->Bidinfo->newEmptyEntity();
      $bidinfo->biditem_id = $id;
      $bidrequest = $this->Bidrequests->find('all', [
        'conditions'=>['biditem_id'=>$id],
        'contain' => ['Users'],
        'order'=>['price'=>'desc']])->first();
      if(!empty($bidrequest)){
        $bidinfo->user_id = $bidrequest->user->id;
        $bidinfo->user = $bidrequest->user;
        $bidinfo->price = $bidrequest->price;
        $this->Bidinfo->save($bidinfo);
      }
      $biditem->bidinfo = $bidinfo;
    }
    $bidrequests = $this->Bidrequests->find('all', [
      'conditions'=>['biditem_id'=>$id],
      'contain' => ['Users'],
      'order'=>['price'=>'desc']])->toArray();
      $this->set(compact('biditem', 'bidrequests'));
  }

  public function add()
  {
    $biditem = $this->Biditems->newEmptyEntity();
    if($this->request->is('post')){
      $biditem = $this->Biditems->patchEntity($biditem, $this->request->getData());
      if($this->Biditems->save($biditem)){
        $this->Flash->success(__('保存しました。'));
        return $this->redirect(['action' => 'index']);
      }
      $this->Flash->error(__('保存に失敗しました。もう一度入力ください。'));
    }
    $this->set(compact('biditem'));
  }

  public function bid($biditem_id = null)
  {
    $bidrequest = $this->Bidrequests->newEmptyEntity();
    $bidrequest->biditem_id = $biditem_id;
    $bidrequest->user_id = $this->Auth->user('id');
    if($this->request->is('post')){
      $bidrequest = $this->Bidrequests->patchEntity($bidrequest, $this->request->getData());
      if($this->Bidrequests->save($bidrequest)){
        $this->Flash->success(__('入札を送信しました。'));
        return $this->redirect(['action'=>'view', $biditem_id]);
      }
      $this->Flash->error(__('入札に失敗しました。もう一度入力ください。'));
    }
    $biditem = $this->Biditems->get($biditem_id);
    $this->set(compact('bidrequest', 'biditem'));
  }

  public function msg($bidinfo_id = null)
  {
    $bidmsg = $this->Bidmessages->newEmptyEntity();
    if($this->request->is('post')){
      $bidmsg = $this->Bidmessages->patchEntity($bidmsg, $this->request->getData());
      if($this->Bidmessages->save($bidmsg)){
        $this->Flash->success(__('保存しました。'));
      } else {
         $this->Flash->error(__('保存に失敗しました。もう一度入力ください。'));
      }
    }
    try{
      $bidinfo = $this->Bidinfo->get($bidinfo_id, ['contain'=>['Biditems']]);
    } catch(Exception $e){
       $bidinfo = null;
    }
    $bidmsgs = $this->Bidmessages->find('all', [
      'conditions'=>['bidinfo_id'=>$bidinfo_id],
      'contain' => ['Users'],
      'order'=>['created'=>'desc']]);
    $this->set(compact('bidmsgs', 'bidinfo', 'bidmsg'));
  }

  public function home()
  {
   $bidinfo = $this->paginate('Bidinfo', [
     'conditions'=>['Bidinfo.user_id'=>$this->Auth->user('id')],
     'contain' => ['Users', 'Biditems'],
     'order'=>['created'=>'desc'],
     'limit' => 10])->toArray();
   $this->set(compact('bidinfo'));
  }

  public function home2()
  {
   $biditems = $this->paginate('Biditems', [
     'conditions'=>['Biditems.user_id'=>$this->Auth->user('id')],
     'contain' => ['Users', 'Bidinfo'],
     'order'=>['created'=>'desc'],
     'limit' => 10])->toArray();
   $this->set(compact('biditems'));
  }

  public function beforeFilter(\Cake\Event\EventInterface $event){
    parent::beforeFilter($event);
    $this->Auth->allow(['login', 'index', 'add', 'view', 'home', 'home2', 'msg', 'bid']); // 後で'add'を削除する
  }

}
