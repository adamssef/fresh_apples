<?php

namespace Drupal\fresh_apples_reviews\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\fresh_apples_show_page\Service\FreshApplesShowPageService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MovieReviewFormUpdateOnly extends MovieReviewForm {

  protected FreshApplesShowPageService $freshApplesShowPageService;

  /**
   * Constructs a new MovieReviewForm.
   *
   * @param FreshApplesShowPageService $fresh_apples_show_page_service
   *   The site path.
   */
  public function __construct(
    protected FreshApplesShowPageService $fresh_apples_show_page_service,
  ) {
    $this->freshApplesShowPageService = $fresh_apples_show_page_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fresh_apples_show_page.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $movie_id = NULL) {
    $form['rating'] = [
      '#type' => 'number',
      '#title' => $this->t('Twoja aktualna ocena (1-10)'),
      '#min' => 1,
      '#max' => 10,
      '#required' => TRUE,
    ];

    $currentUserId = \Drupal::currentUser()->id();
    $this_page_node = \Drupal::routeMatch()->getParameter('node');

    if ($this->fresh_apples_show_page_service->getTheReviewByUidAndShowId($currentUserId, $this_page_node->id())) {
      $prev_rating = $this->fresh_apples_show_page_service->getPrevRating($this_page_node);

      if ($prev_rating) {

        if ($prev_rating) {
          $form['rating']['#default_value'] = $prev_rating;
        }
      }

    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Zaktualizuj'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $currentUserId = \Drupal::currentUser()->id();
    $this_page_node = \Drupal::routeMatch()->getParameter('node');
    $review = $this->fresh_apples_show_page_service->getTheReviewByUidAndShowId($currentUserId, $this_page_node->id());
    $review->set('field_review_rating', $form_state->getValue('rating'));
    $review->save();

    \Drupal::messenger()->addMessage($this->t('Review updated successfully!'));
  }
}
