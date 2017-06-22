<?php
/**
 * 消息操作类
 * @author Linxiangdi
 */
namespace app\common\library;

use app\common\model\CustomersMessage;

class MessageHandle
{
    /**
     * 发送邮件到会员
     * @param string $subject           邮件主题
     * @param string $body              邮件内容
     * @param string $customers_email   收件邮箱
     * @return bool
     */
    public static function sendEmail($subject, $body, $customers_email)
    {
        $email = new Email();
        if ($email->send($subject, $body, $customers_email) !== true) {
            return false;
        };
        return true;
    }
    /**
     * 发送站内信到会员
     * @param string $customers_id  会员ID
     * @param string $image         消息图片
     * @param string $message       消息内容
     * @return bool
     */
    public static function sendInsideMail($customers_id, $orders_id, $orders_model, $message)
    {
        $message = '订单号：' . $orders_model . '<br />' . $message;
        $data = [
            'customers_id' => $customers_id,
            'orders_id' => $orders_id,
            'message' => $message
        ];
        $message = new CustomersMessage();
        $result = $message->save($data);
        if (false !== $result) {
            return $message->getError();
        }
        return true;
    }
}