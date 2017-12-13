<?php 


function array_sign($array, $prepend = '', $sign = '+')
{
    $results = [];

    foreach ($array as $key => $value) 
    {
        if (is_array($value)) {
            $results = array_merge($results, array_sign($value, $prepend.$key.$sign));
        } else {
            $results[$prepend.$key] = $value;
        }
    }

    return $results;
}

function clear_string_from_shit($string, $shit = '.pdf.pdf', $replacement = '.pdf')
{
    if (stripos($string, $shit)) 
    {
        $string = str_replace($shit, $replacement, $string);
        $string = clear_string_from_shit($string, $shit, $replacement);
    }
    return $string;
}