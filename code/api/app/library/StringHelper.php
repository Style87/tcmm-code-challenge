<?php
namespace App\Library;

class StringHelper
{
    const CASE_CAMEL  = 'CAMEL';
    const CASE_KEBAB  = 'KEBAB';
    const CASE_PASCAL = 'PASCAL';
    const CASE_SNAKE  = 'SNAKE';

    /**
     * Preps the input for case change by trimming spaces and converting words to snake case
     *
     * @param string The value to prep.
     *
     * @return string
     */
    static public function prepairInput($input)
    {
        $input = trim($input);
        $input = str_replace(' ', '_', $input);
        return $input;
    }

    /**
     * Returns the snake case value for the given input.
     *
     * @param string The value to convert to snake case.
     *
     * @return string
     */
    static public function toSnakeCase($input)
    {
        $input = self::prepairInput($input);
        $returnString = '';
        switch(self::getStringCase($input))
        {
            case self::CASE_CAMEL:
            case self::CASE_PASCAL:
                preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z])|[A-Za-z][a-z]+|[0-9]+)!', $input, $matches);
                $ret = $matches[0];
                foreach ($ret as &$match) {
                    $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
                }
                $returnString = implode('_', $ret);
                break;
            case self::CASE_KEBAB:
                $returnString = str_replace('-', '_', $input);
                break;
            case self::CASE_SNAKE:
                $returnString = $input;
                break;
        }

        return $returnString;
    }

    /**
     * Returns the camel case value for the given input.
     *
     * @param string The value to convert to camel case.
     *
     * @return string
     */
    static public function toCamelCase($input)
    {
        $input = self::prepairInput($input);
        $returnString = '';
        switch(self::getStringCase($input))
        {
            case self::CASE_CAMEL:
                $returnString = $input;
                break;
            case self::CASE_PASCAL:
                $returnString = lcfirst($input);
                break;
            case self::CASE_KEBAB:
            case self::CASE_SNAKE:
                $returnString = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[_|-]/', ' ', $input))));
                break;
        }

        return $returnString;
    }

    /**
     * Returns the pascal case value for the given input.
     *
     * @param string The value to convert to pascal case.
     *
     * @return string
     */
    static public function toPascalCase($input)
    {
        $input = self::prepairInput($input);
        $returnString = '';
        switch(self::getStringCase($input))
        {
            case self::CASE_PASCAL:
                $returnString = $input;
                break;
            case self::CASE_CAMEL:
                $returnString = ucfirst($input);
                break;
            case self::CASE_KEBAB:
            case self::CASE_SNAKE:
                $returnString = str_replace(' ', '', ucwords(preg_replace('/[_|-]/', ' ', $input)));
                break;
        }

        return $returnString;
    }

    /**
     * Returns the kebab case value for the given input.
     *
     * @param string The value to convert to snake case.
     *
     * @return string
     */
    static public function toKebabCase($input)
    {
        $input = self::prepairInput($input);
        $returnString = '';
        switch(self::getStringCase($input))
        {
            case self::CASE_CAMEL:
            case self::CASE_PASCAL:
                preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z])|[A-Za-z][a-z]+|[0-9]+)!', $input, $matches);
                $ret = $matches[0];
                foreach ($ret as &$match) {
                    $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
                }
                $returnString = implode('-', $ret);
                break;
            case self::CASE_KEBAB:
                $returnString = $input;
                break;
            case self::CASE_SNAKE:
                $returnString = str_replace('_', '-', $input);
                break;
        }

        return $returnString;
    }

    /**
     * Returns the case type for the given input.
     *
     * @param string $input
     *
     * @return string
     */
    static public function getStringCase($input)
    {
        if (strpos($input, '-') !== false)
        {
            return self::CASE_KEBAB;
        }
        else if (strpos($input, '_') !== false)
        {
            return self::CASE_SNAKE;
        }
        else if (ctype_upper(substr($input, 0,1)))
        {
            return self::CASE_PASCAL;
        }
        else
        {
            return self::CASE_CAMEL;
        }
    }

    /**
     * Returns true if the haystack starts with the needle
     *
     * @param string $input
     *
     * @return string
     */
    static public function startsWith($haystack, $needle) : bool {
      if (is_array($needle)) {
        foreach ($needle as $el) {
          if (substr_compare($haystack, $el, 0, strlen($el), true) === 0) {
            return true;
          }
        }
        return false;
      }

      return substr_compare($haystack, $needle, 0, strlen($needle), true) === 0;
    }

    /**
     * Returns true if the haystack starts with the needle
     *
     * @param string $input
     *
     * @return string
     */
    static public function endsWith($haystack, $needle) : bool {
      if (is_array($needle)) {
        foreach ($needle as $el) {
          if (substr_compare($haystack, $el, -strlen($el), strlen($el), true) === 0) {
            return true;
          }
        }
        return false;
      }

      return substr_compare($haystack, $needle, -strlen($needle), strlen($needle), true) === 0;
    }

    /**
     * Removes single or double quotes from around a string
     *
     * @param string $haystack
     *
     * @return string
     */
    static public function removeQuotes($haystack) : string {
      if (
        (StringHelper::startsWith($haystack, '"') && StringHelper::endsWith($haystack, '"'))
        || (StringHelper::startsWith($haystack, "'") && StringHelper::endsWith($haystack, "'"))
      ) {
        $haystack = substr($haystack, 1, strlen($haystack)-2);
      }
      return $haystack;
    }

    /**
     * Gets a class name from a fully qualified class with namespace.
     *
     * @param string $input
     *
     * @return string
     */
    static public function getShortName($input) : string {
      return substr(strrchr($input, "\\"), 1);
    }
}
