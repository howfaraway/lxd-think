<?php
namespace app\common\library;
use app\common\exception\ServiceException;
use PHPMailer\PHPMailer\PHPMailer;
use think\Log;

class Email {
    /**
     * @var PHPMailer
     */
    private $mail;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->mail = new PHPMailer;
    }

    /**
     * 邮件发送
     * @param string $subject    邮件主题
     * @param string $body       邮件内容
     * @param string $to         收件邮箱
     * @return bool
     * @throws ServiceException
     */
    public function send($subject, $body, $to)
    {
        $this->initEmail();
        $this->mail->addAddress($to);
        $this->mail->WordWrap = 50;
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;
        if (!$this->mail->send()) {
            Log::record('send email fail: ' . $this->mail->ErrorInfo);
            throw new ServiceException('发送邮件失败');
        }
        return true;
    }

    /**
     * 邮件对象初始化
     */
    private function initEmail()
    {
        $this->mail->clearAddresses();
        $this->mail->isSMTP();
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Host = 'smtp.qq.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'service@haitaole.hk';
        $this->mail->Password = 'hfspecslkj212';
        $this->mail->SMTPSecure = '';
        $this->mail->Port = 25;
        $this->mail->From = strtoupper($this->mail->Username);
        $this->mail->FromName = '海淘乐';
    }

}

