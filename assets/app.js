// Confirmaciones de borrado
document.querySelectorAll('[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) {
      event.preventDefault();
    }
  });
});

// Filtros y validaciones interactivas para todos los módulos
document.addEventListener('DOMContentLoaded', () => {
  // 1. Filtro en tiempo real para texto alfabético (nombres, apellidos y búsquedas por nombre)
  document.querySelectorAll('input[data-filter="alpha"], input[data-filter="alpha-search"]').forEach(input => {
    // Bloquear en pulsación teclas numéricas o símbolos prohibidos
    input.addEventListener('keydown', (e) => {
      // Permitir teclas de control/navegación
      if (['Backspace', 'Tab', 'Enter', 'Escape', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Delete', 'Home', 'End'].includes(e.key)) {
        return;
      }
      // Permitir combinaciones con Ctrl/Cmd (Copiar, Pegar, Cortar, Seleccionar todo)
      if (e.ctrlKey || e.metaKey) {
        return;
      }
      // Verificar si la tecla es un número o símbolo no permitido
      const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]$/;
      if (!regex.test(e.key)) {
        e.preventDefault();
      }
    });

    // Limpiar al escribir o pegar texto
    input.addEventListener('input', () => {
      const sanitized = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]/g, '');
      if (input.value !== sanitized) {
        input.value = sanitized;
      }
    });

    // Limpiar espacios dobles o extremos al salir del campo
    input.addEventListener('blur', () => {
      input.value = input.value.trim().replace(/\s+/g, ' ');
    });
  });

  // Validación de formularios de búsqueda por nombre
  document.querySelectorAll('form.toolbar, form.heading-search').forEach(form => {
    form.addEventListener('submit', (e) => {
      const searchInput = form.querySelector('input[data-filter="alpha-search"]');
      if (searchInput) {
        const val = searchInput.value.trim();
        if (val !== '' && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+$/.test(val)) {
          alert('La búsqueda solo admite letras y espacios (sin números ni símbolos).');
          searchInput.focus();
          e.preventDefault();
          return;
        }
      }
    });
  });

  // 2. Filtro y cálculo interactivo de edad en fecha de nacimiento
  const birthInput = document.querySelector('input[data-filter="birth-date"]');
  const birthFeedback = document.getElementById('birth-date-feedback');

  function validateBirthDate() {
    if (!birthInput || !birthFeedback) return true;
    const value = birthInput.value;
    if (!value) {
      birthFeedback.innerHTML = '';
      birthFeedback.className = 'field-feedback';
      return false;
    }

    const birthDate = new Date(value + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (isNaN(birthDate.getTime())) {
      birthFeedback.className = 'field-feedback error';
      birthFeedback.textContent = 'Fecha de nacimiento inválida.';
      return false;
    }

    if (birthDate > today) {
      birthFeedback.className = 'field-feedback error';
      birthFeedback.textContent = 'La fecha no puede ser futura.';
      return false;
    }

    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }

    if (age < 14) {
      birthFeedback.className = 'field-feedback error';
      birthFeedback.textContent = `Edad calculada: ${age} años (mínimo 14 años requeridos).`;
      return false;
    } else if (age > 95) {
      birthFeedback.className = 'field-feedback error';
      birthFeedback.textContent = `Edad calculada: ${age} años (supera el límite de 95 años).`;
      return false;
    } else {
      birthFeedback.className = 'field-feedback success';
      birthFeedback.textContent = `✓ Edad calculada: ${age} años (rango admitido).`;
      return true;
    }
  }

  if (birthInput) {
    birthInput.addEventListener('input', validateBirthDate);
    birthInput.addEventListener('change', validateBirthDate);
    // Ejecutar al cargar si ya tiene valor
    if (birthInput.value) {
      validateBirthDate();
    }
  }

  // 3. Filtro interactivo de archivo de fotografía (tamaño y tipo MIME)
  document.querySelectorAll('[data-photo-preview]').forEach(input => {
    const preview = input.closest('.photo-upload')?.querySelector('.preview-photo');
    const feedback = document.getElementById('photo-feedback');
    const initialSrc = preview ? preview.src : '';

    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (!file) {
        if (feedback) {
          feedback.innerHTML = '';
          feedback.className = 'field-feedback';
        }
        if (preview && initialSrc) preview.src = initialSrc;
        return;
      }

      const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
      const maxBytes = 3 * 1024 * 1024; // 3 MB

      if (!validTypes.includes(file.type)) {
        if (feedback) {
          feedback.className = 'field-feedback error';
          feedback.textContent = `⚠️ Formato no admitido (${file.type || 'archivo'}). Solo JPG, PNG o WEBP.`;
        }
        input.value = '';
        if (preview && initialSrc) preview.src = initialSrc;
        return;
      }

      if (file.size > maxBytes) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        if (feedback) {
          feedback.className = 'field-feedback error';
          feedback.textContent = `⚠️ El archivo supera los 3 MB permitidos (${sizeMb} MB). Selecciona otra imagen.`;
        }
        input.value = '';
        if (preview && initialSrc) preview.src = initialSrc;
        return;
      }

      // Archivo válido
      const sizeKb = (file.size / 1024).toFixed(0);
      if (feedback) {
        feedback.className = 'field-feedback success';
        feedback.textContent = `✓ Fotografía válida: ${file.name} (${sizeKb} KB).`;
      }
      if (preview) {
        preview.src = URL.createObjectURL(file);
      }
    });
  });

  // 4. Validación final al intentar enviar el formulario de alumno
  const studentForm = document.querySelector('form.form-panel');
  if (studentForm) {
    studentForm.addEventListener('submit', (e) => {
      const firstNameInput = document.getElementById('first_name');
      const lastNameInput = document.getElementById('last_name');

      if (firstNameInput) {
        const val = firstNameInput.value.trim();
        if (val.length < 2 || !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+$/.test(val)) {
          alert('Por favor ingresa un nombre válido (solo letras y espacios, mínimo 2 caracteres).');
          firstNameInput.focus();
          e.preventDefault();
          return;
        }
      }

      if (lastNameInput) {
        const val = lastNameInput.value.trim();
        if (val.length < 2 || !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+$/.test(val)) {
          alert('Por favor ingresa un apellido válido (solo letras y espacios, mínimo 2 caracteres).');
          lastNameInput.focus();
          e.preventDefault();
          return;
        }
      }

      if (birthInput && !validateBirthDate()) {
        alert('Por favor ingresa una fecha de nacimiento válida (edad entre 14 y 95 años).');
        birthInput.focus();
        e.preventDefault();
        return;
      }
    });
  }

  // 5. Filtros para inputs de catálogos (Carreras, Cursos, Semestres, Secciones)
  document.querySelectorAll('input[data-catalog-filter]').forEach(input => {
    const filterType = input.dataset.catalogFilter;
    const feedback = input.closest('.catalog-input-group')?.querySelector('.catalog-feedback');

    function validateCatalogInput() {
      const val = input.value.trim();
      const min = parseInt(input.getAttribute('minlength') || '1', 10);
      const max = parseInt(input.getAttribute('maxlength') || '100', 10);

      if (!val) {
        if (feedback) {
          feedback.innerHTML = '';
          feedback.className = 'field-feedback catalog-feedback';
        }
        return false;
      }

      if (val.length < min) {
        if (feedback) {
          feedback.className = 'field-feedback catalog-feedback error';
          feedback.textContent = `Mínimo ${min} caracteres (faltan ${min - val.length}).`;
        }
        return false;
      }

      let isValid = true;
      let errorMsg = '';

      if (filterType === 'alpha-punct') {
        // Carreras: solo letras, espacios, puntos, guiones y apóstrofes
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/.test(val)) {
          isValid = false;
          errorMsg = 'Solo se permiten letras, espacios y puntos (sin números).';
        }
      } else if (filterType === 'course') {
        // Cursos: letras, números, signos básicos, al menos una letra
        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\:\'\-\(\)]+$/.test(val) || !/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/.test(val)) {
          isValid = false;
          errorMsg = 'Debe incluir al menos una letra y no usar símbolos especiales prohibidos.';
        }
      } else if (filterType === 'semester') {
        // Semestres: ciclo y año, al menos una letra
        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\/\'\-]+$/.test(val) || !/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/.test(val)) {
          isValid = false;
          errorMsg = 'Formato inválido. Debe contener ciclo y año (ej. Primer semestre 2026).';
        }
      } else if (filterType === 'section') {
        // Secciones: letras, dígitos, guiones
        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-]+$/.test(val)) {
          isValid = false;
          errorMsg = 'Solo se permiten letras, números y guiones.';
        }
      }

      if (!isValid) {
        if (feedback) {
          feedback.className = 'field-feedback catalog-feedback error';
          feedback.textContent = errorMsg;
        }
        return false;
      } else {
        if (feedback) {
          feedback.className = 'field-feedback catalog-feedback success';
          feedback.textContent = `✓ Formato válido (${val.length}/${max} caracteres)`;
        }
        return true;
      }
    }

    // Interceptar teclas inválidas según el tipo de catálogo
    input.addEventListener('keydown', (e) => {
      if (['Backspace', 'Tab', 'Enter', 'Escape', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Delete', 'Home', 'End'].includes(e.key) || e.ctrlKey || e.metaKey) {
        return;
      }

      let allowedRegex;
      if (filterType === 'alpha-punct') {
        allowedRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]$/;
      } else if (filterType === 'course') {
        allowedRegex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\:\'\-\(\)]$/;
      } else if (filterType === 'semester') {
        allowedRegex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\/\'\-]$/;
      } else if (filterType === 'section') {
        allowedRegex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-]$/;
      }

      if (allowedRegex && !allowedRegex.test(e.key)) {
        e.preventDefault();
      }
    });

    // Sanitizar en input
    input.addEventListener('input', () => {
      let sanitized = input.value;
      if (filterType === 'alpha-punct') {
        sanitized = sanitized.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '');
      } else if (filterType === 'course') {
        sanitized = sanitized.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\:\'\-\(\)]/g, '');
      } else if (filterType === 'semester') {
        sanitized = sanitized.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\/\'\-]/g, '');
      } else if (filterType === 'section') {
        sanitized = sanitized.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-]/g, '');
      }
      if (input.value !== sanitized) {
        input.value = sanitized;
      }
      validateCatalogInput();
    });

    input.addEventListener('blur', () => {
      input.value = input.value.trim().replace(/\s+/g, ' ');
      validateCatalogInput();
    });
  });

  // Validación al enviar formulario de catálogo
  document.querySelectorAll('form.catalog-add').forEach(form => {
    form.addEventListener('submit', (e) => {
      const input = form.querySelector('input[data-catalog-filter]');
      if (!input) return;
      const min = parseInt(input.getAttribute('minlength') || '1', 10);
      const val = input.value.trim();
      if (val.length < min) {
        alert(`El nombre debe tener al menos ${min} caracteres.`);
        input.focus();
        e.preventDefault();
        return;
      }
      const pattern = input.getAttribute('pattern');
      if (pattern && !new RegExp(pattern).test(val)) {
        alert(input.title || 'El valor no cumple con el formato requerido.');
        input.focus();
        e.preventDefault();
        return;
      }
    });
  });

  // 6. Filtros para notas académicas (0 a 100 con status dinámico)
  document.querySelectorAll('input[data-filter="grade-score"]').forEach(input => {
    function updateScoreState() {
      const raw = input.value.trim();
      input.classList.remove('score-pass', 'score-fail');
      if (raw === '') return;
      const num = parseInt(raw, 10);
      if (!isNaN(num)) {
        if (num >= 60) {
          input.classList.add('score-pass');
        } else {
          input.classList.add('score-fail');
        }
      }
    }

    input.addEventListener('keydown', (e) => {
      // Bloquear teclas no numéricas: 'e', 'E', '+', '-', '.', ','
      if (['e', 'E', '+', '-', '.', ','].includes(e.key)) {
        e.preventDefault();
      }
    });

    input.addEventListener('input', () => {
      let val = input.value.replace(/[^0-9]/g, '');
      if (val !== '') {
        let num = parseInt(val, 10);
        if (num > 100) num = 100;
        if (num < 0) num = 0;
        input.value = num.toString();
      }
      updateScoreState();
    });

    // Evaluar estado al cargar
    updateScoreState();
  });

  // 10. Alternar visibilidad de contraseña (Login)
  const togglePasswordBtn = document.getElementById('togglePasswordBtn');
  const passwordInput = document.getElementById('password');
  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

      const eyeOpen = togglePasswordBtn.querySelector('.icon-eye-open');
      const eyeClosed = togglePasswordBtn.querySelector('.icon-eye-closed');
      if (eyeOpen && eyeClosed) {
        eyeOpen.style.display = isPassword ? 'none' : 'inline-flex';
        eyeClosed.style.display = isPassword ? 'inline-flex' : 'none';
      }

      togglePasswordBtn.setAttribute('title', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
      togglePasswordBtn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
      passwordInput.focus();
    });
  }

  // 11. Burbuja de perfil y menú desplegable (Dashboard)
  const profileBubbleBtn = document.getElementById('profileBubbleBtn');
  const profileDropdown = document.getElementById('profileDropdown');
  if (profileBubbleBtn && profileDropdown) {
    const toggleDropdown = (open) => {
      const willOpen = typeof open === 'boolean' ? open : !profileDropdown.classList.contains('is-open');
      if (willOpen) {
        profileDropdown.classList.add('is-open');
        profileBubbleBtn.setAttribute('aria-expanded', 'true');
        profileDropdown.setAttribute('aria-hidden', 'false');
      } else {
        profileDropdown.classList.remove('is-open');
        profileBubbleBtn.setAttribute('aria-expanded', 'false');
        profileDropdown.setAttribute('aria-hidden', 'true');
      }
    };

    profileBubbleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleDropdown();
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
      if (!profileDropdown.contains(e.target) && !profileBubbleBtn.contains(e.target)) {
        toggleDropdown(false);
      }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && profileDropdown.classList.contains('is-open')) {
        toggleDropdown(false);
        profileBubbleBtn.focus();
      }
    });
  }

  // 12. Componente de Desplegables Personalizados (Custom Select Dropdown)
  function initCustomSelects() {
    const selects = document.querySelectorAll('select.custom-select, select.filter-select, .report-filter-item select, .heading-filter-select select, select[data-custom-select]');
    selects.forEach(select => {
      if (select.dataset.customized === 'true') return;
      select.dataset.customized = 'true';

      const isFilter = (select.classList.contains('filter-select') ||
                        select.dataset.icon === 'filter' ||
                        select.closest('.report-filter-item, .heading-filter-select, .filter-bar') !== null ||
                        ['period_id', 'career_id', 'course_id', 'section_id', 'status'].includes(select.name)) &&
                       !select.closest('.form-panel');

      const container = document.createElement('div');
      container.className = 'custom-select-container';
      if (isFilter) container.classList.add('is-filter-dropdown');
      if (select.disabled) container.classList.add('is-disabled');

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'custom-select-trigger';
      trigger.setAttribute('aria-haspopup', 'listbox');
      trigger.setAttribute('aria-expanded', 'false');

      if (isFilter) {
        const filterIcon = document.createElement('span');
        filterIcon.className = 'custom-select-filter-icon';
        filterIcon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>';
        trigger.appendChild(filterIcon);
      }

      const label = document.createElement('span');
      label.className = 'custom-select-label';

      const arrow = document.createElement('span');
      arrow.className = 'custom-select-arrow';
      arrow.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';

      trigger.appendChild(label);
      trigger.appendChild(arrow);
      container.appendChild(trigger);

      const dropdown = document.createElement('div');
      dropdown.className = 'custom-select-dropdown';
      dropdown.setAttribute('role', 'listbox');

      // Si tiene más de 6 opciones, añadir buscador interno
      const optionsCount = select.options.length;
      let searchInput = null;
      if (optionsCount > 6) {
        const searchBox = document.createElement('div');
        searchBox.className = 'custom-select-search';
        searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar opción...';
        searchInput.autocomplete = 'off';
        searchBox.appendChild(searchInput);
        dropdown.appendChild(searchBox);
      }

      const optionsList = document.createElement('div');
      optionsList.className = 'custom-select-options-list';
      dropdown.appendChild(optionsList);
      container.appendChild(dropdown);

      // Reemplazar posición del select en el DOM
      select.parentNode.insertBefore(container, select);
      select.classList.add('custom-select-native');
      container.appendChild(select);

      let focusedIndex = -1;

      function renderOptions(filterText = '') {
        optionsList.innerHTML = '';
        const filter = filterText.toLowerCase().trim();
        let matchCount = 0;
        focusedIndex = -1;

        Array.from(select.options).forEach((opt, idx) => {
          const text = opt.text;
          const val = opt.value;
          const isSelected = opt.selected;

          if (filter !== '' && !text.toLowerCase().includes(filter)) {
            return;
          }
          matchCount++;

          const optEl = document.createElement('div');
          optEl.className = 'custom-select-option' + (isSelected ? ' is-selected' : '');
          optEl.setAttribute('role', 'option');
          optEl.setAttribute('data-value', val);
          optEl.setAttribute('data-index', idx);

          const textSpan = document.createElement('span');
          textSpan.className = 'custom-select-option-text';
          textSpan.textContent = text;

          const checkIcon = document.createElement('span');
          checkIcon.className = 'custom-select-check';
          checkIcon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

          optEl.appendChild(textSpan);
          optEl.appendChild(checkIcon);

          optEl.addEventListener('click', (e) => {
            e.stopPropagation();
            selectOption(val, text);
            closeDropdown();
          });

          optionsList.appendChild(optEl);
        });

        if (matchCount === 0) {
          const emptyEl = document.createElement('div');
          emptyEl.className = 'custom-select-empty';
          emptyEl.textContent = 'Sin resultados';
          optionsList.appendChild(emptyEl);
        }
      }

      function updateSelectedDisplay() {
        const selectedOpt = select.selectedOptions[0] || select.options[0];
        label.textContent = selectedOpt ? selectedOpt.text : 'Seleccionar...';
        renderOptions(searchInput ? searchInput.value : '');
      }

      function selectOption(val, text) {
        if (select.value === val) return;
        select.value = val;
        label.textContent = text;

        // Disparar eventos de cambio nativos
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));

        // Ejecutar manejador inline onchange si existe
        if (typeof select.onchange === 'function') {
          select.onchange.call(select);
        } else if (select.hasAttribute('onchange')) {
          try {
            new Function(select.getAttribute('onchange')).call(select);
          } catch (e) {
            console.error(e);
          }
        } else if (isFilter && select.form) {
          select.form.submit();
        }
      }

      function openDropdown() {
        // Cerrar cualquier otro custom select abierto
        document.querySelectorAll('.custom-select-container.is-open').forEach(c => {
          if (c !== container) c.classList.remove('is-open');
        });
        container.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        if (searchInput) {
          searchInput.value = '';
          renderOptions();
          setTimeout(() => searchInput.focus(), 50);
        } else {
          renderOptions();
        }
      }

      function closeDropdown() {
        container.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus();
      }

      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        if (container.classList.contains('is-open')) {
          closeDropdown();
        } else {
          openDropdown();
        }
      });

      if (searchInput) {
        searchInput.addEventListener('click', (e) => e.stopPropagation());
        searchInput.addEventListener('input', () => {
          renderOptions(searchInput.value);
        });
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            closeDropdown();
          } else if (e.key === 'Enter') {
            e.preventDefault();
            const firstOption = optionsList.querySelector('.custom-select-option');
            if (firstOption) {
              const val = firstOption.getAttribute('data-value');
              const txt = firstOption.querySelector('.custom-select-option-text').textContent;
              selectOption(val, txt);
              closeDropdown();
            }
          }
        });
      }

      container.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && container.classList.contains('is-open')) {
          closeDropdown();
        }
      });

      // Escuchar cambios externos en el select
      select.addEventListener('change', () => {
        const selectedOpt = select.selectedOptions[0];
        if (selectedOpt) {
          label.textContent = selectedOpt.text;
        }
      });

      updateSelectedDisplay();
    });
  }

  // Cerrar todos los custom selects al hacer clic fuera
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-select-container')) {
      document.querySelectorAll('.custom-select-container.is-open').forEach(c => {
        c.classList.remove('is-open');
        const trig = c.querySelector('.custom-select-trigger');
        if (trig) trig.setAttribute('aria-expanded', 'false');
      });
    }
  });

  // Inicializar desplegables personalizados
  initCustomSelects();
  window.initCustomSelects = initCustomSelects;
});


