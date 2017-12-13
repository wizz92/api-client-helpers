<?php

    
/*

    Little helper for our check function.

    */
    function is_ok($func)
    {
        return ($func()) ? 'OK' : 'OFF';
    }

     /*

    Validating that all our configs necessary for frontend repo are in place.

    */
    function validate_frontend_config()
    {
        if(! conf('frontend_repo_url')) return false;

        if(substr(conf('frontend_repo_url'), -1) != '/') return false;

        return true;
    }

       

    /*

    Validating that all our configs necessary for redirect are in place.

    */
    function validate_redirect_config()
    {
        if(! conf('secret_url', false)) return false;

        return true;
    }
