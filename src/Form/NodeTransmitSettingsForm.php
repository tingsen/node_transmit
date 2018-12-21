<?php
namespace Drupal\node_transmit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node_transmit\NTStatisticsHelper;

class NodeTransmitSettingsForm extends FormBase{

    public function getFormId()
    {
        // TODO: Implement getFormId() method.
        return 'drupal_form_node_transmit_settings';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // TODO: Implement buildForm() method.
        $settings = NTStatisticsHelper::getSettings();

        $form['ip_start'] = [
            '#type' => 'textfield',
            '#title' => '内网ip段开端',
            '#default_value' => $settings ? $settings[1]->ip_start:'',
        ];
        $form['ip_end'] = [
            '#type' => 'textfield',
            '#title' => '内网ip段结束',
            '#default_value' => $settings ? $settings[1]->ip_end:'',

        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('保存'),
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (empty($form_state->getValue('ip_start'))) {
            $form_state->setErrorByName('ip_start', $this->t('请设置IP开端'));
        }
        if (empty($form_state->getValue('ip_end'))) {
            $form_state->setErrorByName('ip_end', $this->t('请设置IP末端'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // TODO: Implement submitForm() method.
        // Truncate table.
        NTStatisticsHelper::truncate();

        $ip_start = $form_state->getValue('ip_start');
        $ip_end = $form_state->getValue('ip_end');

        $field = [
            'type' => '1',
            'ip_start' => $ip_start,
            'ip_end' => $ip_end,
        ];
        NTStatisticsHelper::insert('node_transmit_ip', $field);
        drupal_set_message('设置成功');
    }

}