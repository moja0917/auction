<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="users form">
<?= $this->Flash->render() ?>
<?= $this->Form->create() ?>
 <fieldset>
   <legend>アカウント名とパスワードを入力してください。</legend>
   <?= $this->Form->input('username') ?>
   <?= $this->Form->input('password') ?>
 </fieldset>
<?= $this->Form->button(__('Login')); ?>
<?= $this->Form->end() ?>
</div>
