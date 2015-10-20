<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class NewsletterOptionField extends Model {
  public static $_table = MP_NEWSLETTER_OPTION_FIELDS_TABLE;

  function __construct() {
    parent::__construct();
    $this->addValidations('name', array(
      'required' => __('You need to specify a name.')
    ));
    $this->addValidations('newsletter_type', array(
      'required' => __('You need to specify a newsletter type.')
    ));
  }

  function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Newsletter',
      __NAMESPACE__ . '\NewsletterOption',
      'option_field_id',
      'newsletter_id'
    )->select_expr(MP_NEWSLETTER_OPTIONS_TABLE.'.value');
  }
}
