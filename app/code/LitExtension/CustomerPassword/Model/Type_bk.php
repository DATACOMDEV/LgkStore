<?php
/**
 * @project: CustomerPassword
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

namespace LitExtension\CustomerPassword\Model;

class Type
{
    public function run($customerModel, $email, $password){
        if($customer_id = $customerModel->getId()){
            $pw_hash = $customerModel->getPasswordHash();
            if(!$pw_hash){
                return false;
            }
            try{
                $check = $this->validatePassword($customerModel, $email, $password, $pw_hash);
            }catch (\Exception $e){
                return false;
            }
            if($check){
                $hash = $customerModel->hashPassword($password);
                $customerModel->setPasswordHash($hash);
                try{
                    $customerModel->save();
                }catch (\Exception $e){}
                return true;
            }
        }
        return false;
    }

    public function validatePassword($customerModel, $email, $password, $pw_hash){
        return false;
    }
}