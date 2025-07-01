// script.js - JavaScript para validación y funcionalidades

document.addEventListener('DOMContentLoaded', function() {
    const pqrsForm = document.getElementById('pqrsForm');
    
    if (pqrsForm) {
        // Validación del formulario antes de enviar
        pqrsForm.addEventListener('submit', function(event) {
            let isValid = true;
            const errorMessages = [];
            
            // Validar nombre
            const nombre = document.getElementById('nombre').value.trim();
            if (nombre === '') {
                errorMessages.push('El nombre es obligatorio');
                isValid = false;
            }
            
            // Validar documento
            const documento = document.getElementById('documento').value.trim();
            if (documento === '') {
                errorMessages.push('El documento de identidad es obligatorio');
                isValid = false;
            }
            
            // Validar email
            const email = document.getElementById('email').value.trim();
            if (email === '') {
                errorMessages.push('El correo electrónico es obligatorio');
                isValid = false;
            } else if (!validateEmail(email)) {
                errorMessages.push('El formato del correo electrónico no es válido');
                isValid = false;
            }
            
            // Validar ciudad
            const ciudad = document.getElementById('ciudad').value;
            if (ciudad === '') {
                errorMessages.push('Debe seleccionar una ciudad');
                isValid = false;
            }
            
            // Validar EPS
            const eps = document.getElementById('eps').value;
            if (eps === '') {
                errorMessages.push('Debe seleccionar una EPS');
                isValid = false;
            }
            
            // Validar tipo de pqrs
            const tipoPQRS = document.getElementById('tipo_pqrs').value;
            if (tipoPQRS === '') {
                errorMessages.push('Debe seleccionar un motivo de pqrs');
                isValid = false;
            }
            
            // Validar descripción
            const descripcion = document.getElementById('descripcion').value.trim();
            if (descripcion === '') {
                errorMessages.push('La descripción es obligatoria');
                isValid = false;
            } else if (descripcion.length < 20) {
                errorMessages.push('La descripción debe tener al menos 20 caracteres');
                isValid = false;
            }
            
            // Si hay errores, mostrarlos y prevenir el envío del formulario
            if (!isValid) {
                event.preventDefault();
                
                // Mostrar los errores en la página
                showErrors(errorMessages);
            }
        });
        
        // Limpiar mensajes de error cuando se resetea el formulario
        pqrsForm.addEventListener('reset', function() {
            clearErrors();
        });
    }
    
    // Función para validar formato de email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Función para mostrar mensajes de error
    function showErrors(errors) {
        // Eliminar mensajes de error anteriores
        clearErrors();
        
        // Crear contenedor de errores si no existe
        let errorContainer = document.querySelector('.error-message');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.className = 'error-message';
            pqrsForm.insertBefore(errorContainer, pqrsForm.firstChild);
        }
        
        // Crear lista de errores
        const errorList = document.createElement('ul');
        errorList.style.marginLeft = '20px';
        
        // Agregar cada error a la lista
        errors.forEach(function(error) {
            const errorItem = document.createElement('li');
            errorItem.textContent = error;
            errorList.appendChild(errorItem);
        });
        
        // Agregar título y lista al contenedor
        const errorTitle = document.createElement('strong');
        errorTitle.textContent = 'Por favor corrija los siguientes errores:';
        errorContainer.appendChild(errorTitle);
        errorContainer.appendChild(errorList);
        
        // Hacer scroll al inicio del formulario
        window.scrollTo(0, pqrsForm.offsetTop);
    }
    
    // Función para limpiar mensajes de error
    function clearErrors() {
        const errorContainer = document.querySelector('.error-message');
        if (errorContainer) {
            errorContainer.remove();
        }
    }
    
    // Mejoras para los campos del formulario
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(function(input) {
        // Remover mensajes de error cuando el usuario comienza a escribir
        input.addEventListener('input', function() {
            if (document.querySelector('.error-message')) {
                clearErrors();
            }
        });
        
        // Destacar campo activo
        input.addEventListener('focus', function() {
            this.style.boxShadow = '0 0 5px rgba(52, 152, 219, 0.5)';
        });
        
        input.addEventListener('blur', function() {
            this.style.boxShadow = 'none';
        });
    });
});

// Funciones para la página de administración (si aplica)
function confirmarEliminar(id) {
    if (confirm('¿Está seguro de que desea eliminar esta pqrs?')) {
        window.location.href = 'admin/eliminar_pqrs.php?id=' + id;
    }
}

function filtrarPQRS() {
    const filtro = document.getElementById('filtro').value;
    const estado = document.getElementById('filtro_estado').value;
    const url = 'admin/lista_pqrss.php?filtro=' + encodeURIComponent(filtro) + '&estado=' + encodeURIComponent(estado);
    window.location.href = url;
}

// cargar ciudades
document.addEventListener('DOMContentLoaded', function() {
    // Cargar las ciudades cuando la página esté lista
    fetch('cargar_ciudades.php?formato=json')
        .then(response => response.json())
        .then(ciudades => {
            const selectCiudad = document.getElementById('ciudad');
            
            // Opción por defecto
            selectCiudad.innerHTML = '<option value="">Seleccione una ciudad</option>';
            
            // Agregar cada ciudad como una opción
            ciudades.forEach(ciudad => {
                const option = document.createElement('option');
                option.value = ciudad.id;
                option.textContent = ciudad.nombre;
                selectCiudad.appendChild(option);
            });
        })
        .catch(error => console.error('Error al cargar ciudades:', error));
});


// Cargar EPSs
document.addEventListener('DOMContentLoaded', function() {
    // Cargar las EPSs cuando la página esté lista
    fetch('cargar_eps.php?formato=json')
        .then(response => response.json())
        .then(eps => {
            const selectEps = document.getElementById('eps');
            
            // Opción por defecto
            selectEps.innerHTML = '<option value="">Seleccione una EPS</option>';
            
            // Agregar cada ciudad como una opción
            eps.forEach(eps => {
                const option = document.createElement('option');
                option.value = eps.id;
                option.textContent = eps.nombre;
                selectEps.appendChild(option);
            });
        })
        .catch(error => console.error('Error al cargar EPS:', error));
});

// Cargar tipos de pqrs
document.addEventListener('DOMContentLoaded', function() {
    // Cargar los Tipos de pqrs cuando la página esté lista
    fetch('cargar_tipos_pqrs.php?formato=json')
        .then(response => response.json())
        .then(tiposPQRS => {
            const selectTiposPQRS = document.getElementById('tiposPQRS');
            
            // Opción por defecto
            selectTiposPQRS.innerHTML = '<option value="">Seleccione una Tipo de PQRS</option>';
            
            // Agregar cada Tipo de pqrs como una opción
            tiposPQRS.forEach(tiposPQRS => {
                const option = document.createElement('option');
                option.value = tiposPQRS.id;
                option.textContent = tiposPQRS.nombre;
                selectTiposPQRS.appendChild(option);
            });
        })
        .catch(error => console.error('Error al cargar Tipos de pqrs:', error));
});


