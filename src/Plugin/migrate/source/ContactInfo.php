<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'contact_info' migrate source.
 *
 * @MigrateSource(
 *  id = "contact_info",
 *  source_module = "utexas_migrate"
 * )
 */
class ContactInfo extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('utexas_contact_info', 'b')
      ->fields('b', array_keys($this->fields()));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Entity ID'),
      'label' => $this->t('label'),
      'name' => $this->t('name'),
      'field_location_1' => $this->t('Location 1'),
      'field_location_2' => $this->t('Location 2'),
      'field_location_3' => $this->t('Location 3'),
      'field_location_city' => $this->t('Location City'),
      'field_location_state' => $this->t('Location State'),
      'field_location_zip' => $this->t('Location Zip'),
      'field_address_1' => $this->t('Address 1'),
      'field_address_2' => $this->t('Address 2'),
      'field_address_3' => $this->t('Address 3'),
      'field_address_city' => $this->t('Address City'),
      'field_address_state' => $this->t('Address State'),
      'field_address_zip' => $this->t('Address Zip'),
      'field_url' => $this->t('URL'),
      'field_phone' => $this->t('Phone'),
      'field_fax' => $this->t('Fax'),
      'field_email' => $this->t('Email'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $fields = [];
    if ($row->getSourceProperty('field_location_1')) {
      $location = $row->getSourceProperty('field_location_1');
      if ($row->getSourceProperty('field_location_2')) {
        $location .= PHP_EOL . $row->getSourceProperty('field_location_2');
      }
      if ($row->getSourceProperty('field_location_3')) {
        $location .= PHP_EOL . $row->getSourceProperty('field_location_3');
      }
      if ($row->getSourceProperty('field_location_city')) {
        $location .= PHP_EOL . $row->getSourceProperty('field_location_city');
      }
      if ($row->getSourceProperty('field_location_state')) {
        $separator = $row->getSourceProperty('field_location_city') ? ', ' : '';
        $location .= $separator . $row->getSourceProperty('field_location_state');
      }
      if ($row->getSourceProperty('field_location_zip')) {
        $location .= ' ' . $row->getSourceProperty('field_location_zip');
      }
      $fields[] = [
        'header' => 'Location:',
        'content_value' => $location,
        'content_format' => 'restricted_html',
      ];
    }
    if ($row->getSourceProperty('field_address_1')) {
      $address = $row->getSourceProperty('field_address_1');
      if ($row->getSourceProperty('field_address_2')) {
        $address .= PHP_EOL . $row->getSourceProperty('field_address_2');
      }
      if ($row->getSourceProperty('field_address_3')) {
        $address .= PHP_EOL . $row->getSourceProperty('field_address_3');
      }
      if ($row->getSourceProperty('field_address_city')) {
        $address .= PHP_EOL . $row->getSourceProperty('field_address_city');
      }
      if ($row->getSourceProperty('field_address_state')) {
        $separator = $row->getSourceProperty('field_address_city') ? ', ' : '';
        $address .= $separator . $row->getSourceProperty('field_address_state');
      }
      if ($row->getSourceProperty('field_address_zip')) {
        $address .= ' ' . $row->getSourceProperty('field_address_zip');
      }
      $fields[] = [
        'header' => 'Address:',
        'content_value' => $address,
        'content_format' => 'restricted_html',
      ];
    }
    if ($email = $row->getSourceProperty('field_email')) {
      $fields[] = [
        'header' => 'Email:',
        'content_value' => '<a href="tel:' . $email . '">' . $email . '</a>',
        'content_format' => 'restricted_html',
      ];
    }
    if ($phone = $row->getSourceProperty('field_phone')) {
      $fields[] = [
        'header' => 'Phone:',
        'content_value' => '<a href="tel:' . $phone . '">' . $phone . '</a>',
        'content_format' => 'restricted_html',
      ];
    }
    if ($fax = $row->getSourceProperty('field_fax')) {
      $fields[] = [
        'header' => 'Fax:',
        'content_value' => $fax,
        'content_format' => 'restricted_html',
      ];
    }
    if ($url = $row->getSourceProperty('field_url')) {
      $fields[] = [
        'header' => 'Website:',
        'content_value' => $url,
        'content_format' => 'restricted_html',
      ];
    }
    $row->setSourceProperty('fields', $fields);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
