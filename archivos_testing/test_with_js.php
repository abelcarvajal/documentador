<?php
use Symfony\Component\HttpFoundation\Response;

class TestController {
    public function indexAction() {
        return new Response('
            <script src="jquery.min.js"></script>
            <script src="bootstrap.js"></script>
            <script>
                $(document).ready(function() {
                    import { createApp } from "vue";
                    require("lodash");
                    // código JS...
                });
            </script>
        ');
    }
}