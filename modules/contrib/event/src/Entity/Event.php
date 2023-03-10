<?php

namespace Drupal\event\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\user\UserInterface;

/**
 * Defines the Event entity.
 *
 * @ingroup event
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event"),
 *   bundle_label = @Translation("Event types"),
 *   handlers = {
 *     "storage" = "Drupal\event\EventStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\event\EventListBuilder",
 *     "views_data" = "Drupal\event\Entity\EventViewsData",
 *     "translation" = "Drupal\event\EventTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\event\Form\EventForm",
 *       "add" = "Drupal\event\Form\EventForm",
 *       "edit" = "Drupal\event\Form\EventForm",
 *       "delete" = "Drupal\event\Form\EventDeleteForm",
 *     },
 *     "access" = "Drupal\event\EventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\event\EventHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event",
 *   data_table = "event_field_data",
 *   revision_table = "event_revision",
 *   revision_data_table = "event_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer event entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "machine_name" = "machine_name"
 *   },
 *   links = {
 *     "add-page" = "/event/add",
 *     "add-form" = "/event/add/{event_type}",
 *     "canonical" = "/event/{event}",
 *     "collection" = "/admin/content/events",
 *     "edit-form" = "/event/{event}/edit",
 *     "delete-form" = "/event/{event}/delete",
 *     "version-history" = "/event/{event}/revisions",
 *     "revision" = "/event/{event}/revisions/{event_revision}/view",
 *     "revision_revert" = "/event/{event}/revisions/{event_revision}/revert",
 *     "revision_delete" = "/event/{event}/revisions/{event_revision}/delete",
 *     "translation_revert" = "/event/{event}/revisions/{event_revision}/revert/{langcode}",
 *   },
 *   bundle_entity_type = "event_type",
 *   field_ui_base_route = "entity.event_type.edit_form"
 * )
 */
class Event extends RevisionableContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the event owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->get('machine_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($name) {
    $this->set('machine_name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setUnpublished() {
    $this->set('status', FALSE);
  }

  /**
   * Checks that an existing machine name does not already exist.
   *
   * This is a static method so it can be used by a machine name field.
   *
   * @param string $machine_name
   *   The machine name to load the entity by.
   *
   * @return \Drupal\event\Entity\Event|array
   *   Loaded Link entity or NULL if not found.
   */
  public static function loadByMachineName($machine_name) {
    $storage = \Drupal::service('entity.manager')->getStorage('event');
    $result = $storage->getQuery()
      ->condition('machine_name', $machine_name)
      ->execute();
    return $result ? $storage->loadMultiple($result) : [];
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Event entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event Name'))
      ->setDescription(t('The name of the Event.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('Machine (Short) name of the event'))
      ->setSetting('max_length', 32)
      ->addConstraint('UniqueField', [])
      ->addPropertyConstraints('value', ['Regex' => ['pattern' => '/^[a-z0-9_]+$/']])
      ->setDisplayOptions('form', [
        'type' => 'machine_name',
        'weight' => -5,
        'settings' => [
          'source_field' => 'name',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Event is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['event_date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Event Date'))
      ->setDescription(t('Date (Time) for an Event.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'datetime_type' => DateRangeItem::DATETIME_TYPE_DATETIME,
        'timezone_storage' => TRUE,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'daterange_default',
        'settings' => [
          'timezone_override' => '',
          'timezone_per_date' => TRUE,
        ],
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue('');

    return $fields;
  }

}
