/**
 * Builder Fieldset Accordion JavaScript
 * Div-basierte Akkordeon-Interaktion für <fieldset> Elemente
 */

(function () {
  'use strict';

  function readBool(container, attrName, defaultValue) {
    var value = container.getAttribute(attrName);
    if (value === null) {
      return defaultValue;
    }
    return value === '1' || value === 'true';
  }

  function readInitial(container) {
    var value = container.getAttribute('data-accordion-initial');
    if (value === 'all' || value === 'none' || value === 'first') {
      return value;
    }
    return 'first';
  }

  function setOpenState(fieldset, open) {
    var body = fieldset.querySelector('.builder-fieldset-body');
    var legend = fieldset.querySelector('.builder-fieldset-legend');
    if (!body || !legend) {
      return;
    }

    fieldset.classList.toggle('accordion-open', open);
    body.classList.toggle('accordion-collapsed', !open);
    legend.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function collapseSiblings(fieldset) {
    var container = fieldset.closest('.builder-fieldsets');
    if (!container) {
      return;
    }

    if (!readBool(container, 'data-accordion-single', true)) {
      return;
    }

    var siblings = container.querySelectorAll('.builder-fieldset');
    siblings.forEach(function (sibling) {
      if (sibling !== fieldset) {
        setOpenState(sibling, false);
      }
    });
  }

  function toggleFromLegend(legend) {
    var fieldset = legend.closest('.builder-fieldset');
    if (!fieldset) {
      return;
    }

    var container = fieldset.closest('.builder-fieldsets');
    var collapsible = container ? readBool(container, 'data-accordion-collapsible', true) : true;

    var isOpen = fieldset.classList.contains('accordion-open');
    if (isOpen) {
      if (!collapsible) {
        return;
      }
      setOpenState(fieldset, false);
      return;
    }

    collapseSiblings(fieldset);
    setOpenState(fieldset, true);
  }

  function initAccordions(root) {
    var scope = root || document;
    var containers = scope.querySelectorAll('.builder-fieldsets');

    containers.forEach(function (container) {
      if (container.getAttribute('data-accordion-initialized') === '1') {
        return;
      }

      var initialMode = readInitial(container);
      var fieldsets = container.querySelectorAll('.builder-fieldset');
      fieldsets.forEach(function (fieldset, index) {
        var shouldOpen = false;
        if (initialMode === 'all') {
          shouldOpen = true;
        } else if (initialMode === 'first') {
          shouldOpen = index === 0;
        }
        setOpenState(fieldset, shouldOpen);
      });

      container.setAttribute('data-accordion-initialized', '1');
    });
  }

  document.addEventListener('click', function (event) {
    var legend = event.target.closest('.builder-fieldset-legend');
    if (!legend) {
      return;
    }

    event.preventDefault();
    toggleFromLegend(legend);
  });

  document.addEventListener('keydown', function (event) {
    var legend = event.target.closest('.builder-fieldset-legend');
    if (!legend) {
      return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      toggleFromLegend(legend);
    }
  });

  document.addEventListener('shown.bs.modal', function (event) {
    initAccordions(event.target);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initAccordions(document);
    });
  } else {
    initAccordions(document);
  }

  window.builderInitAccordions = initAccordions;
})();
