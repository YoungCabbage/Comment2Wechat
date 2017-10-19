<?php
/**
 * 微信推送评论通知（原作者：<a href="https://yian.me">Y!an</a>）
 * 
 * @package Comment2Wechat
 * @author 神代綺凜
 * @version 2.0
 * @link https://lolico.moe
 */
class Comment2Wechat_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
    
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('Comment2Wechat_Plugin', 'sc_send');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('Comment2Wechat_Plugin', 'sc_send');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback = array('Comment2Wechat_Plugin', 'sc_send');
        
        return _t('请配置此插件的 SCKEY, 以使您的微信推送生效');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $key = new Typecho_Widget_Helper_Form_Element_Text('sckey', NULL, NULL, _t('SCKEY'), _t('想要获取 SCKEY 则需要在 <a href="https://sc.ftqq.com/">Server酱</a> 使用 Github 账户登录<br>同时，注册后需要在 <a href="http://sc.ftqq.com/">Server酱</a> 绑定你的微信号才能收到推送'));
        $form->addInput($key->addRule('required', _t('您必须填写一个正确的 SCKEY')));
        
        $notMyself = new Typecho_Widget_Helper_Form_Element_Radio('notMyself',
            array(
                '1' => '是',
                '0' => '否'
            ),'1', _t('当评论者为自己时不发送通知'), _t('启用后，若评论者为博主，则不会发送微信通知'));
        $form->addInput($notMyself);
        
        $enableHttps = new Typecho_Widget_Helper_Form_Element_Radio('enableHttps',
            array(
                '1' => '是',
                '0' => '否'
            ),'1', _t('使用 HTTPS 提交微信推送请求'), _t('Server酱已支持 HTTPS，启用后将使用 HTTPS 提交微信推送请求，更安全<br><br><br>
            此插件由原作者 <a href="https://yian.me">Y!an</a> 的 <a href="https://github.com/YianAndCode/Comment2Wechat">Comment2Wechat 1.0.0</a> 插件修改而来<br>本插件项目地址：<a href="https://github.com/YKilin/Comment2Wechat">https://github.com/YKilin/Comment2Wechat</a>'));
        $form->addInput($enableHttps);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 微信推送
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function sc_send($comment, $post)
    {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Comment2Wechat');

        $sckey = $options->sckey;
        $notMyself = $options->notMyself;
        $enableHttps = $options->enableHttps;
        
        if($comment['authorId'] == 1 && $notMyself == '1'){
            return  $comment;
        }

        $text = "有人在您的博客发表了评论";
        $desp = "**".$comment['author']."** 在你的博客中说到：\n\n > ".$comment['text'];

        $postdata = http_build_query(
            array(
                'text' => $text,
                'desp' => $desp
                )
            );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        if($enableHttps == '1'){
            $opts = array('http' =>
            array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                ),
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false
                )
            );
        }
        
        $ftqq = "http://sc.ftqq.com/";
        if($enableHttps == '1'){
            $ftqq = "https://sc.ftqq.com/";
        }
        
        $context  = stream_context_create($opts);
        $result = file_get_contents($ftqq.$sckey.'.send', false, $context);
        return  $comment;
    }
}
