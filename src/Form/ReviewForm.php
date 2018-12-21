<?php
namespace Drupal\node_transmit\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node_transmit\NTStatisticsHelper;



class ReviewForm extends FormBase {

    public function getFormId(){
        return 'review_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $type = NULL,$node = NULL)
    {
        // TODO: Implement buildForm() method.
        $path = \Drupal::service('path.alias_manager')->getPathByAlias('/'.$type.'/'.$node);
        if(preg_match('/node\/(\d+)/', $path, $matches)) {
            $node ? $nodes = Node::load($matches[1]) : '';
        }
        $current_user = \Drupal::currentUser();

        $form['name'] =[
            '#type' => 'textfield',
            '#title' => '用户名',
            '#default_value' => $current_user ? $current_user->getAccountName() : '',
            '#placeholder' => '用户名',

        ];

        $form['email'] =[
            '#type' => 'email',
            '#title' => '邮箱',
            '#placeholder' => '邮箱',
            '#default_value' => $current_user ? $current_user->getEmail() : '',
            '#required' => TRUE,
        ];
        $form['help'] = [
            '#type' => 'item',
            '#title' => '您要申请的文档：',
            '#markup' => $nodes->getTitle() ? $nodes->getTitle():''
        ];
        $form['captcha'] = array(
            '#type' => 'captcha',
            '#captcha_type' => 'default',
        );
        $form['node_id'] =[
            '#type' => 'hidden',
            '#value' => $nodes->id(),
        ];
        $form['title'] =[
            '#type' => 'hidden',
            '#value' => $nodes->getTitle() ? $nodes->getTitle():'',
        ];
        $form['actions'] = [
            '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('申请传递'),
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */

    public function submitForm(array &$form,FormStateInterface $form_state){
        // TODO: Implement submitForm() method.
        // Truncate table.
        //NTStatisticsHelper::truncate();
        $name = $form_state->getValue('name');
        $email = $form_state->getValue('email');
        $node_id= $form_state->getValue('node_id');
        $title= $form_state->getValue('title');
        $user_id = $this->currentUser()->id();
        $created = time();

        $field = [
            'type' => '1',
            'email' => $email,
            'name' => $name,
            'user_id' => $user_id,
            'node_id' => $node_id,
            'title' => $title,
            'status' => '1',
            'created' => $created,
        ];
        $verif = NTStatisticsHelper::virif($user_id,$node_id);
        if(!empty($verif) && $verif[0]['status'] == 1){
            drupal_set_message('正在审核中,请耐心等待');
            $form_state->setRedirect('yd_statistics.profile');
        }elseif (!empty($verif) && $verif[0]['status'] == 3){
            $update = ['status'=> 1];
            NTStatisticsHelper::status($verif[0]['id'],$update);
            drupal_set_message('重新提交成功');
            $form_state->setRedirect('yd_statistics.profile');
        }else{
            NTStatisticsHelper::insert('node_transmit_verification',$field);
            drupal_set_message('已经提交');
            $form_state->setRedirect('yd_statistics.profile');
        }
    }
}