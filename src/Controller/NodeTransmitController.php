<?php

namespace Drupal\node_transmit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\node_transmit\NTStatisticsHelper;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class NodeTransmitController extends ControllerBase{
    //内网ip文献传递node为文献id
    public function import($type, $nid){
        $current_user = \Drupal::currentUser();
        $is_admin = in_array('administrator', array_values($current_user->getRoles()));

        $set_ip = NTStatisticsHelper::getSettings();
        $user_ip = $this->get_iplong($_SERVER['REMOTE_ADDR']);

        $path = \Drupal::service('path.alias_manager')->getPathByAlias('/'.$type.'/'.$nid);
        if(preg_match('/node\/(\d+)/', $path, $matches)) {
            $entity = Node::load($matches[1]);
        }

        if(empty($entity)){  //资源不存在
            drupal_set_message(t("Sorry, the resource you want to download does not exist."), 'error');
            $url = Url::fromRoute('system.404');
            return new RedirectResponse($url->toString());
        }

        $allow = FALSE;
        if($set_ip){
            $ip_start = $this->get_iplong($set_ip[1]->ip_start);
            $ip_end = $this->get_iplong($set_ip[1]->ip_end);
            if($user_ip >= $ip_start && $user_ip <= $ip_end) {//校内用户允许直接下载
                $allow = TRUE;
            }
        }

        if(!empty($current_user) && $current_user->isAuthenticated()){
            if($is_admin || $allow){
                return NTStatisticsHelper::downloadPDF($entity);
            }else{
                $url = Url::fromRoute('node_transmit.review', ['type'=> $type, 'node' => $nid]);
                return new RedirectResponse($url->toString());
            }
        }else if($allow){
            return NTStatisticsHelper::downloadPDF($entity);
        }else{
            //没有设置校内IP，且没有登录，跳转登录页面。
            return new RedirectResponse(Url::fromRoute('user.login')->toString());
        }
    }
    /**
     * ip处理成整数
     * {@inheritdoc}
     * bindec(decbin(ip2long('这里填ip地址')));
     * ip2long();的意思是将IP地址转换成整型
     * 之所以要decbin和bindec一下是为了防止IP数值过大int型存储不了出现负数
     */
    public function get_iplong($ip){
        return bindec(decbin(ip2long($ip)));
    }
    //审核列表
    public function user_list()
    {
        $build['node_transmit_filter_form']  = \Drupal::formBuilder()->getForm('Drupal\node_transmit\Form\NodeTransmitFilterForm');
        $connection = \Drupal::database();
        $header = [
            [
                'data' => $this->t('id'),
                'field' => 'w.id',
            ],
            [
                'data' => $this->t('用户'),
                'field' => 'w.name',
                'sort' => 'desc',
            ],
            [
                'data' => $this->t('文献'),
                'field' => 'w.node_list',
            ],
            [
                'data' => $this->t('状态'),
                'field' => 'w.status',
            ],
            [
                'data' => $this->t('操作'),
                'width' => 200,
                'field' => 'w.status',
            ],
        ];
        $query = $connection->select('node_transmit_verification', 'w')
            ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
            ->extend('\Drupal\Core\Database\Query\TableSortExtender');
        $query->fields('w', [
            'id',
            'name',
            'email',
            'type',
            'user_id',
            'title',
            'msg',
            'node_id',
            'status',
        ]);
        $filters = [];
        if(!empty($_SESSION['node_transmit_filter'])){
            $where = $args = [];
            foreach ($_SESSION['node_transmit_filter'] as $key => $value) {
                if($key=='status' && $value == 0) continue;
                if(!empty($value)){
                    $filter_where = [];
                    $filter_where[] = 'w.'.$key.' = ?';
                    $args[] = $value;
                    if (!empty($filter_where)) {
                        $where[] = '(' . implode(' OR ', $filter_where) . ')';
                    }
                }
            }
            $where = !empty($where) ? implode(' AND ', $where) : '';
            $filters = [
                'where' => $where,
                'args' => $args
            ];
        }
        if (!empty($filters['where'])) {
            $query->where($filters['where'], $filters['args']);
        }
        $result = $query
            ->limit(50)
            ->orderByHeader($header)
            ->execute();
        foreach ($result as $dblog) {
            $rows[] = [
                $this->t($dblog->id),
                $this->t($dblog->name),
                $this->t($dblog->title),
                $this->condition($dblog->status,$dblog->msg),
                $this->buildAction($dblog->id, $dblog->status)
            ];
        }
        $build['dblog_table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => isset($rows) ? $rows : [],
            '#attributes' => ['id' => 'admin-dblog', 'class' => ['admin-dblog']],
            '#empty' => $this->t('No log messages available.'),
            '#attached' => [
                'library' => ['dblog/drupal.dblog'],
            ],
        ];

        $build['dblog_pager'] = ['#type' => 'pager'];
        return $build;
    }

    protected function buildFilterQuery() {
        if (empty($_SESSION['node_transmit_filter'])) {
            return;
        }

        // Build query.
        $where = $args = [];
        foreach ($_SESSION['node_transmit_filter'] as $key => $filter) {
            $filter_where = [];
            foreach ($filter as $value) {
                $filter_where[] = 'w.severity = ?';
                $args[] = $value;
            }
            if (!empty($filter_where)) {
                $where[] = '(' . implode(' OR ', $filter_where) . ')';
            }
        }
        $where = !empty($where) ? implode(' AND ', $where) : '';

        return [
            'where' => $where,
            'args' => $args,
        ];
    }
    //审核状态
    public function buildAction($id, $status){
        $action = [];
        if($status == 1) {
            $action = [
                'data' => [[
                    '#type' => 'html_tag',
                    '#attributes' => ['data-r' => $id, 'class' => ['button button--primary checked-button']],
                    '#tag' => 'button',
                    '#value' => '通过',
                    '#attached' => [
                        'library' => ['node_transmit/node_transmit.check'],
                    ]
                ],[
                    '#type' => 'html_tag',
                    '#attributes' => ['data-r' => $id, 'class' => ['button unchecked-button']],
                    '#tag' => 'button',
                    '#value' => '驳回',
                    '#attached' => [
                        'library' => ['node_transmit/node_transmit.check'],
                    ]
                ]]
            ];
        }
        return $action;
    }
    //审核通过操作
    public function update(){
        $id = $_POST['id'];
        $fields = [
          'status' => 2
        ];
        $b = NTStatisticsHelper::query($id);
        $params['subject'] = $this->t('华东科技大学');
        $params['body'] = [$this->t('你申请的文献已经通过,请前往个人中心下载')];
        \Drupal::service('plugin.manager.mail')->mail('smtp', 'smtp-test', $b[0]['email'], true, $params);
        NTStatisticsHelper::status($id,$fields);
        $response = new Response();
        $response->setContent("成功");
        return $response;
    }
    //驳回操作
    public function reject(){
        $id = $_POST['id'];
        $msg = $_POST['msg'];
        $fields = [
            'status' => 3,
            'msg' => $msg,
        ];
        $b = NTStatisticsHelper::query($id);
        $params['subject'] = $this->t('华东科技大学');
        $params['body'] = [$this->t('你申请的文献被驳回理由：'.$msg)];
        \Drupal::service('plugin.manager.mail')->mail('smtp', 'smtp-test', $b[0]['email'], true, $params);
        NTStatisticsHelper::status($id,$fields);
        $response = new Response();
        $response->setContent("成功");
        return $response;
    }
    //用户审核状态
    public function condition($status,$msg){
        switch($status){
            case 2:
                return '已审核';
                break;
            case 3:
                return '已驳回/理由：'.$msg;
                break;
            default:
                return '未审核';
        }
    }

    //数据同步api
    public function autoapi(Request $request){
        try {
            $data = file_get_contents("php://input");
            $arr = json_decode($data, true);
            //签名验证
            // 验证请求， 10分钟失效
            if (time() - $arr['timestamp'] > 600) {
                return false;
            }
//        局域网验证
//        $ip = $_SERVER['REMOTE_ADDR'];
//        $ip ? true:false;
            $keys = hash('sha256', 'huazheng' . $arr['timestamp']);
            sort($keys);
            $sign = strtoupper($keys);
            $result = [
                'code' => -1
            ];
            if ($arr['sign'] == $sign && $arr['data']) {
                $success = array();
                foreach ($arr['data'] as $k => $v) {
                    if ($v['tags'] == 'law') {
                        if (!empty($v['title'])) {
                                if (strlen($v['title']) > 255) {
                                    $node['title'] = substr($v['title'], 200) . '...';
                                } else {
                                    $node['title'] = $v['title'];
                                }
                            }
                            $node['type'] = 'law';
                            $node['field_published'] = $v['promulgatingDate'];
                            $node['field_document_number'] = $v['documentNumber'];
                            $node['body'] = $v['body'];
                            if (!empty($v['keyword'])) {
                                $node['field_keyword'] = $v['keyword'];
                            }
                            if (!empty($v['url'])) {
                                $node['field_url'] = $v['url'];
                            }
                            //发证机关
                            if (!empty($v['promulgatingAgency'])) {
                                $value = explode(";", $v['promulgatingAgency']);
                                $node['field_promulgating_agency'] = $value;
                            }
                            //地域
                            if (!empty($v['jurisdiction'])) {
                                self::upInsertTerm_I($v['jurisdiction'], 'jurisdiction');
                                $value = self::TermRefs_I($v['jurisdiction'], 'jurisdiction');
                                $node['field_jurisdiction'] = $value;
                            }
                            //效力级别
                            if (!empty($v['levelEffect'])) {
                                self::upInsertTerm_I($v['levelEffect'], 'level_effect');
                                $value = self::TermRefs_I($v['levelEffect'], 'level_effect');
                                $node['field_level_effect'] = $value;
                            }
                            //时效性
                            if (!empty($v['validityStatus'])) {
                                self::upInsertTerm_I($v['validityStatus'], 'validity_status');
                                $value = self::TermRefs_I($v['validityStatus'], 'validity_status');
                                $node['field_validity_status'] = $value;
                            }
                            //行业分类
                            if (!empty($v['industryClassification'])) {
                                self::upInsertTerm_I($v['industryClassification'], 'industry_classification');
                                $value = self::TermRefs_I($v['industryClassification'], 'industry_classification');
                                $node['field_industry_classification'] = $value;
                            }
                            //主题分类
                            if (!empty($v['topicClassification'])) {
                                self::upInsertTerm_I($v['topicClassification'], 'topic_classification');
                                $value = self::TermRefs_I($v['topicClassification'], 'topic_classification');
                                $node['field_topic_classification'] = $value;
                            }
                            //语言
                            if (!empty($v['lang'])) {
                                self::upInsertTerm_I($v['lang'], 'lang');
                                $value = self::TermRefs_I($v['lang'], 'lang');
                                $node['field_lang'] = $value;
                            }
                    } elseif ($v['tags'] == 'case') {
                        if (!empty($v['title'])) {
                            if (strlen($v['title']) > 255) {
                                $node['title'] = substr($v['title'], 200) . '...';
                            } else {
                                $node['title'] = $v['title'];
                            }
                        }
                        $node['type'] = 'case';
                        $node['field_court'] = $v['court'];
                        $node['field_document_number'] = $v['documentNumber'];
                        $node['body'] = $v['body'];
                        if (!empty($v['keyword']) || !empty($v['subjects'])) {
                            $node['field_keyword'] = $v['keyword'].';'.$v['subjects'];
                        }
                        if (!empty($v['url'])) {
                            $node['field_url'] = $v['url'];
                        }
                        //审判程序
                        if (!empty($v['instance'])) {
                            self::upInsertTerm_I($v['instance'], 'instance');
                            $value = self::TermRefs_I($v['instance'], 'instance');
                            $node['field_instance'] = $value;
                        }
                        //文书类型
                        if (!empty($v['typeOfDecision'])) {
                            self::upInsertTerm_I($v['typeOfDecision'], 'type_of_decision');
                            $value = self::TermRefs_I($v['typeOfDecision'], 'type_of_decision');
                            $node['field_type_of_decision'] = $value;
                        }
                        //案由
                        if (!empty($v['causeOfAction'])) {
                            self::upInsertTerm_I($v['causeOfAction'], 'cause_of_action');
                            $value = self::TermRefs_I($v['causeOfAction'], 'cause_of_action');
                            $node['field_cause_of_action'] = $value;
                        }
                        //语言
                        if (!empty($v['lang'])) {
                            self::upInsertTerm_I($v['lang'], 'lang');
                            $value = self::TermRefs_I($v['lang'], 'lang');
                            $node['field_lang'] = $value;
                        }
                    } elseif ($v['tags'] == 'article') {
                        if (!empty($v['title'])) {
                            if (strlen($v['title']) > 255) {
                                $node['title'] = substr($v['title'], 200) . '...';
                            } else {
                                $node['title'] = $v['title'];
                            }
                        }
                        if (!empty($v['keyword'])) {
                            $node['field_keyword'] = $v['keyword'];
                        }
                        if (!empty($v['url'])) {
                            $node['field_url'] = $v['url'];
                        }
                        //类型
                        if (!empty($v['type'])) {
                            self::upInsertTerm_I($v['type'], 'article_type');
                            $value = self::TermRefs_I($v['type'], 'article_type');
                            $node['field_type'] = $value;
                        }
                        //语言
                        if (!empty($v['lang'])) {
                            self::upInsertTerm_I($v['lang'], 'lang');
                            $value = self::TermRefs_I($v['lang'], 'lang');
                            $node['field_lang'] = $value;
                        }
                        $node['type'] = 'article';
                        $node['field_published'] = $v['published'];
                        $node['field_host'] = $v['host'];
                        $node['field_conference'] = $v['conference'];
                        $node['field_place'] = $v['place'];
                        $node['field_author'] = $v['author'];
                        $node['field_author_note'] = $v['authorNote'];
                        $node['field_other_author'] = $v['otherAuthor'];
                        $node['field_layout'] = $v['layout'];
                        $node['field_layout_no'] = $v['layoutNo'];
                        $node['field_organization'] = $v['organization'];
                        $node['field_doi'] = $v['doi'];
                        $node['field_source_page'] = $v['sourPage'];
                        $node['field_issn'] = $v['issn'];
                        $node['field_source_site'] = $v['sourceSite'];
                        $node['field_source_desc'] = $v['sourceDesc'];
                        $node['field_source_en'] = $v['sourEN'];
                        $node['field_source_no'] = $v['sourNo'];
                        //$node['field_type'] = $v['type'];
                        $node['field_source'] = $v['source'];
                        $node['field_classificationnumber'] = $v['classificationNumber'];
                        if (!empty($v['body'])) {
                            $node['body'] = $v['body'];
                        } else {
                            $node['body'] = $v['description'];
                        }
                    }


                    if (empty($node)) {
                        continue;
                    }
                    //检查内容是否存在
                    $b = self::NodeByTitle_I($v['title']);
                    if ($b) {  //TODO 更新已存在的记录
                        $b->save();
                        $success[] = $v['id'];
                    } else {
                        $entity = Node::create($node);
                        if ($entity) {
                            $entity->setPublished(TRUE);
                            $entity->set('moderation_state', "published");
                            $entity->save();
                            $success[] = $v['id'];
                        }
                    }
                }
                if (count($success) > 0) {
                    $result['code'] = 0;
                    $result['success'] = $success;
                }
            } else {
                $result['msg'] = '签名错误或数据为空';
            }
            return new JsonResponse($result);
        }catch(\Exception $e){
                return $e->getMessage();
        }
    }

    public function NodeByTitle_I($name){
        if(strlen($name) > 255){
            $title = substr($name,200).'...';
        }else{
            $title = $name;
        }
        $query = \Drupal::entityQuery('node');
        $query->condition('title', $title);
        $nodes = $query->execute();
        if($nodes){
            $node = Node::load(reset($nodes));
            return $node;
        }else{
            return false;
        }
    }

    public function TermRefs_I($cage, $vid) {
        $terms = explode(";", $cage);
        $refs = array();
        if(!empty($terms)){
            foreach ($terms as $term) {
                if(!empty($term)){
                    $category = explode("/", $term);
                    if(!empty($category)){
                        foreach ($category as $k=>$v){
                            $tid = self::TidByName_I($v, $vid);
                            if(!empty($tid)){
                                $refs[] = $tid;
                            }
                        }
                    }else{
                        foreach ($term as $k=>$v){
                            $tid = self::TidByName_I($v, $vid);
                            if(!empty($tid)){
                                $refs[] = $tid;
                            }
                        }
                    }
                }
            }
        }
        return $refs;
    }

    public function TidByName_I($name = NULL, $vid = NULL) {
        $properties = [];
        if (!empty($name)) {
            $properties['name'] = $name;
        }
        if (!empty($vid)) {
            $properties['vid'] = $vid;
        }
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
        $term = reset($terms);

        return !empty($term) ? $term->id() : null;
    }

    public function upInsertTerm_I($val, $vid){
        if($val){
            $val = explode(";", $val);
            foreach ($val as $vs){
                $vs = explode("/", $vs);
                $parent = null;
                foreach ($vs as $v){
                    $tid = self::TidByName_I($v, $vid);
                    if($tid){
                        $parent = $tid;
                    }else{
                        $term = Term::create([
                            'vid' => $vid,
                            'name' => $v
                        ]);
                        if($parent){
                            $term->parent = $parent;
                        }
                        $term->save();
                        $parent = $term->id();
                    }
                }
            }
        }
    }
}
