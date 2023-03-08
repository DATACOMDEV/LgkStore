<?php

namespace LitExtension\CustomerPassword\Model\Type;

class Shopware extends \LitExtension\CustomerPassword\Model\Type {

    protected $encoder = [];

    public function __construct() {
        $this->encoder = array(
            'md5' => 'Md5',
            'sha256' => 'Sha256',
            'bcrypt' => 'Bcrypt',
        );
    }

    public function getEncoderByName($name)
    {
        $name = strtolower(trim($name));

        if (!isset($this->encoder[$name])) {
            throw new \Exception(sprintf('Encoder by name %s not found', $name));
        }

        return $this->encoder[$name];
    }

    public function validatePassword($customerModel, $email, $password, $hash) {
        $hashArr = explode(':', $hash);

        if(count($hashArr) == 3){
            $encoder = $this->getEncoderByName($hashArr[2]);
            return $encoder->isPasswordValid($password, $hashArr[0] . ':' . $hashArr[1]);
        } elseif (count($hashArr) == 2){
            $encoder = $this->getEncoderByName($hashArr[1]);
            return $encoder->isPasswordValid($password, $hashArr[0]);
        }else{
            $encoder = $this->getEncoderByName('md5');
            return $encoder->isPasswordValid($password, $hash);
        }
    }
}

interface PasswordEncoderInterface
{
    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function isPasswordValid($password, $hash);
}

class Sha256 implements PasswordEncoderInterface
{
    /**
     * @var array
     */
    protected $options = [
        'iterations' => 1000,
        'salt_len' => 22,
    ];

    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function isPasswordValid($password, $hash)
    {
        list($iterations, $salt) = explode(':', $hash);

        $verifyHash = $this->generateInternal($password, $salt, (int) $iterations);

        return hash_equals($hash, $verifyHash);
    }

    /**
     * @param string $password
     * @param string $salt
     * @param int    $iterations
     *
     * @return string
     */
    protected function generateInternal($password, $salt, $iterations)
    {
        $hash = '';
        for ($i = 0; $i <= $iterations; ++$i) {
            $hash = hash('sha256', $hash . $password . $salt);
        }

        return $iterations . ':' . $salt . ':' . $hash;
    }
}

class Md5 implements PasswordEncoderInterface
{
    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function isPasswordValid($password, $hash)
    {
        if (strpos($hash, ':') === false) {
            return hash_equals($hash, md5($password));
        }
        list($md5, $salt) = explode(':', $hash);

        return hash_equals($md5, md5($password . $salt));
    }
}

class Bcrypt implements PasswordEncoderInterface
{
    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function isPasswordValid($password, $hash)
    {
        return password_verify($password, $hash);
    }
}