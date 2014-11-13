<?php
/**
 * Created by JetBrains PhpStorm.
 * User: nike
 * Date: 28.07.11
 * Time: 18:02
 * To change this template use File | Settings | File Templates.
 */
 
class CakePhpTwig_Bootstrap implements  CakePhpTwig_Bootstrapable {

    public static function bootstrapOptions(array &$options)
    {
        // TODO: Implement bootstrapOptions() method.
    }

    public static function bootstrapEnvironment(Twig_Environment $env)
    {
        $env->addExtension(new Twig_Extension_Project());
        $env->addExtension(new Twig_Extension_Text());
    }
}
