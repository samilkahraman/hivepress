(function($) {
  'use strict';

  /**
   * Gets prefixed selector.
   */
  function getSelector(name) {
    return '.hp-js-' + name;
  }

  /**
   * Gets custom object.
   */
  function getObject(name) {
    return $(getSelector(name));
  }

  /**
   * Serializes jQuery object.
   */
  $.fn.serializeObject = function(defaultData) {
    var data = $.extend(true, {}, defaultData);

    $.each(this.serializeArray(), function() {
      if (this.name.slice(-1) == ']') {
        var name = this.name.split('[')[0];

        if (defaultData.hasOwnProperty(name)) {
          name = 'parent_' + name;
        }

        if (!data.hasOwnProperty(name)) {
          data[name] = [];
        }

        data[name].push(this.value);
      } else {
        if (defaultData.hasOwnProperty(this.name)) {
          data['parent_' + this.name] = this.value;
        } else {
          data[this.name] = this.value;
        }
      }
    });

    return data;
  }

  // Link
  $(document).on('click', getSelector('link'), function(e) {
    var link = $(this),
      type = [];

    if (typeof link.data('type') !== 'undefined') {
      type = link.data('type').split(' ');
    }

    if (type.includes('popup')) {
      var url = link.attr('href');

      if (!link.is('a')) {
        url = link.data('url');
      }

      $.fancybox.close();
      $.fancybox.open({
        src: url,
      });
    } else if (type.includes('ajax')) {
      if (link.data('name')) {
        var name = link.attr('data-name');

        link.attr('data-name', link.attr('title'));
        link.attr('title', name);

        if (link.attr('data-state') != 'active') {
          link.attr('data-state', 'active');
        } else {
          link.attr('data-state', '');
        }
      }

      $.post(hpCoreData.ajaxurl, link.data('json'));
    } else {
      if (!link.is('a')) {
        window.location.href = link.data('url');
      }
    }

    if (type.includes('remove')) {
      link.parent().remove();
    }

    e.preventDefault();
  });

  // Slider
  getObject('slider').each(function() {
    var slider = $(this);

    if (slider.data('type') == 'gallery') {
      var images = slider.find('img'),
        image = images.wrap('<div />').parent().wrapAll('<div />').parent(),
        nav = image.clone(),
        name = '';

      if (images.length > 1) {
        slider.html('');

        image.appendTo(slider);
        nav.appendTo(slider).hide();

        $.each(slider.attr('class').split(/\s+/), function(index, item) {
          if (item.match(/^hp-/) && !item.match(/^hp-js-/)) {
            name = item;
          }
        });

        image.addClass(name + '-image').slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          infinite: false,
          arrows: false,
          asNavFor: nav,
        });

        $(window).load(function() {
          nav.addClass(name + '-nav').show().slick({
            slidesToShow: Math.round(slider.width() / 125),
            slidesToScroll: 1,
            infinite: false,
            prevArrow: '<a href="#" class="slick-arrow slick-prev"><i class="fas fa-chevron-left"></i></a>',
            nextArrow: '<a href="#" class="slick-arrow slick-next"><i class="fas fa-chevron-right"></i></a>',
            focusOnSelect: true,
            asNavFor: image,
          });
        });
      }
    }
  });

  // Form
  getObject('form').each(function() {
    var form = $(this),
      type = [];

    if (typeof form.data('type') !== 'undefined') {
      type = form.data('type').split(' ');
    }

    if (type.includes('autosubmit')) {
      form.on('change', function() {
        form.submit();
      });
    }

    if (type.includes('ajax')) {
      var messageContainer = form.find(getSelector('messages')),
        captchaId = $('.g-recaptcha').index(form.find('.g-recaptcha').get(0)),
        submitButton = form.find('input[type="submit"]');

      form.on('submit', function(e) {
        if (submitButton.data('name')) {
          var name = submitButton.attr('data-name');

          submitButton.attr('data-name', submitButton.val());
          submitButton.val(name);

          if (submitButton.attr('data-state') != 'active') {
            submitButton.attr('data-state', 'active');
          } else {
            submitButton.attr('data-state', '');
          }
        } else {
          submitButton.prop('disabled', true);
        }

        $.post(hpCoreData.ajaxurl, form.serializeObject({}), function(response) {
          if (response.hasOwnProperty('status')) {
            if (response.hasOwnProperty('redirect')) {
              if (typeof(response.redirect) === 'boolean') {
                window.location.reload(true);
              } else {
                window.location.replace(response.redirect);
              }
            } else {
              submitButton.prop('disabled', false);

              if (typeof grecaptcha !== 'undefined' && captchaId != -1) {
                grecaptcha.reset(captchaId);
              }

              if (response.status == 'success' && type.includes('reset')) {
                form.trigger('reset');
              }

              messageContainer.html(response.messages);

              if (form.offset().top < $(window).scrollTop()) {
                $('html, body').animate({
                  scrollTop: form.offset().top,
                }, 500);
              }
            }
          }
        });

        e.preventDefault();
      });
    }
  });

  // File upload
  getObject('file-upload').each(function() {
    var field = $(this),
      form = field.closest('form'),
      selectButton = field.parent('button'),
      messageContainer = $('<div />').insertBefore(selectButton),
      responseContainer = selectButton.parent().children('div').first();

    field.fileupload({
      url: hpCoreData.ajaxurl,
      dataType: 'json',
      formData: form.serializeObject(field.data('json')),
      start: function() {
        selectButton.prop('disabled', true);
      },
      stop: function() {
        selectButton.prop('disabled', false);
      },
      done: function(e, data) {
        if (data.result.hasOwnProperty('status')) {
          messageContainer.html(data.result.messages);

          if (data.result.response != '') {
            if (field.prop('multiple')) {
              responseContainer.append(data.result.response);
            } else {
              responseContainer.html(data.result.response);
            }
          }
        }
      }
    });
  });

  // Sortable
  getObject('sortable').each(function() {
    var container = $(this),
      form = container.closest('form');

    container.sortable({
      stop: function() {
        if (container.children().length > 1) {
          $.post(hpCoreData.ajaxurl, form.serializeObject(container.data('json')));
        }
      },
    });
  });

  // Sticky
  $(window).load(function() {
    getObject('sticky').each(function() {
      var container = $(this),
        spacing = 30;

      container.wrapInner('<div />');

      container.children('div').stickySidebar({
        topSpacing: spacing,
        bottomSpacing: spacing,
      });
    });
  });
})(jQuery);