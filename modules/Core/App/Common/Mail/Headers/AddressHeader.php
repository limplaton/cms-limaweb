<?php
 

namespace Modules\Core\App\Common\Mail\Headers;

class AddressHeader extends Header
{
    /**
     * @var array
     */
    protected $addresses;

    /**
     * Initialize header
     *
     * @param  string  $name  The header name
     * @param  string|array  $value
     * @param  string|null  $personName
     */
    public function __construct($name, $value, $personName = null)
    {
        parent::__construct($name, null);

        $this->parseValue($value, $personName);
    }

    /**
     * Get all addresses
     *
     * @return array
     */
    public function getAll()
    {
        return $this->addresses;
    }

    /**
     * Get header email address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->addresses[0]['address'];
    }

    /**
     * Get the address person name
     *
     * @return string|null
     */
    public function getPersonName()
    {
        return $this->addresses[0]['name'];
    }

    /**
     * Parse the header value
     *
     * @param  string|array  $value
     * @param  string|null  $name
     * @return void
     */
    protected function parseValue($value, $name)
    {
        if (! is_array($value)) {
            if (str_contains($value, ',')) {
                $value = explode(',', $value);
            } else {
                // Not parsed header passed
                if (str_contains($value, '<')) {
                    $value = [$value];
                } else {
                    $value = [$value => $name];
                }
            }
        }

        foreach ($value as $email => $name) {
            if (is_int($email)) {
                [$email, $name] = $this->parseHeader($name);
            }

            $this->addresses[] = [
                'address' => trim($email),
                'name' => is_null($name) ? null : trim($name),
            ];
        }
    }

    /**
     * Parse address header
     *
     * @param  string  $header
     * @return array
     */
    protected function parseHeader($header)
    {
        $name = preg_replace('/(.*)<(.*)>/', '\\1', $header);
        $name = trim(str_replace('"', '', $name));

        $email = trim(preg_replace('/(.*)<(.*)>/', '\\2', $header));

        return [$email, $name];
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAll();
    }
}
