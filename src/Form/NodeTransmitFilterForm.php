<?php

namespace Drupal\node_transmit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the database logging filter form.
 *
 * @internal
 */
class NodeTransmitFilterForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'node_transmit_filter_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['name'] =[
            '#type' => 'textfield',
            '#default_value' => isset($_SESSION['node_transmit_filter']['name']) ? $_SESSION['node_transmit_filter']['name']:'',   //TODO 记录过滤条件
            '#placeholder' => '用户名',
        ];
        $form['status'] = [
            '#type' => 'select',
            '#title' => $this->t('状态'),
            '#default_value' => isset($_SESSION['node_transmit_filter']['status']) ? $_SESSION['node_transmit_filter']['status']:'',   //TODO 记录过滤条件
            '#options' => [
                '0' => $this->t('所有'),
                '1' => $this->t('未审核'),
                '2' => $this->t('已审核'),
                '3' => $this->t('驳回'),
            ],
        ];
        $form['actions'] = [
            '#type' => 'actions',
            '#attributes' => ['class' => ['container-inline']],
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('过滤'),
        ];
        if (!empty($_SESSION['node_transmit_filter'])) {
            $form['actions']['reset'] = [
                '#type' => 'submit',
                '#value' => $this->t('Reset'),
                '#limit_validation_errors' => [],
                '#submit' => ['::resetForm'],
            ];
        }
        return $form;
    }

    /**
     * {@inheritdoc}
     */
//    public function validateForm(array &$form, FormStateInterface $form_state) {
//
//    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        if ($form_state->hasValue('name')) {
            $_SESSION['node_transmit_filter']['name'] = $form_state->getValue('name');
        }
        if ($form_state->hasValue('status')) {
            $_SESSION['node_transmit_filter']['status'] = $form_state->getValue('status');
        }
    }

    /**
     * Resets the filter form.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function resetForm(array &$form, FormStateInterface $form_state) {
        $_SESSION['node_transmit_filter'] = [];
    }
}
