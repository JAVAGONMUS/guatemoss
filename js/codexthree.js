// Datos de ejemplo para las categorías
const divisiones = [
    { id: '31', nombre: 'Electrónicos' },
    { id: '32', nombre: 'Hogar' },
    { id: '33', nombre: 'Ropa' }
];

const departamentos = [
    { id: '55', nombre: 'Audio', divisionId: '31' },
    { id: '56', nombre: 'Video', divisionId: '31' },
    { id: '57', nombre: 'Cocina', divisionId: '32' },
    { id: '58', nombre: 'Dormitorio', divisionId: '32' },
    { id: '59', nombre: 'Caballero', divisionId: '33' },
    { id: '60', nombre: 'Dama', divisionId: '33' }
];

const categorias = [
    { id: '200', nombre: 'Auriculares', departamentoId: '55' },
    { id: '201', nombre: 'Altavoces', departamentoId: '55' },
    { id: '202', nombre: 'Televisores', departamentoId: '56' },
    { id: '203', nombre: 'Reproductores', departamentoId: '56' },
    { id: '204', nombre: 'Utensilios', departamentoId: '57' },
    { id: '205', nombre: 'Electrodomésticos', departamentoId: '57' },
    { id: '206', nombre: 'Sábanas', departamentoId: '58' },
    { id: '207', nombre: 'Almohadas', departamentoId: '58' },
    { id: '208', nombre: 'Camisas', departamentoId: '59' },
    { id: '209', nombre: 'Pantalones', departamentoId: '59' },
    { id: '210', nombre: 'Vestidos', departamentoId: '60' },
    { id: '211', nombre: 'Blusas', departamentoId: '60' }
];

// Contador de productos para el código UPC
let productCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    // Llenar los selectores con las opciones
    const divisionSelect = document.getElementById('division');
    const departmentSelect = document.getElementById('department');
    const categorySelect = document.getElementById('category');
    
    // Llenar división
    divisiones.forEach(division => {
        const option = document.createElement('option');
        option.value = division.id;
        option.textContent = `${division.id} - ${division.nombre}`;
        divisionSelect.appendChild(option);
    });
    
    // Manejar cambios en los selectores para actualizar dependientes
    divisionSelect.addEventListener('change', function() {
        // Limpiar y actualizar departamentos
        departmentSelect.innerHTML = '<option value="">Seleccione un departamento</option>';
        categorySelect.innerHTML = '<option value="">Seleccione una categoría</option>';
        
        if (this.value) {
            const filteredDepartments = departamentos.filter(dept => dept.divisionId === this.value);
            filteredDepartments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.id;
                option.textContent = `${dept.id} - ${dept.nombre}`;
                departmentSelect.appendChild(option);
            });
        }
        
        updateUPCCode();
    });
    
    departmentSelect.addEventListener('change', function() {
        // Limpiar y actualizar categorías
        categorySelect.innerHTML = '<option value="">Seleccione una categoría</option>';
        
        if (this.value) {
            const filteredCategories = categorias.filter(cat => cat.departamentoId === this.value);
            filteredCategories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = `${cat.id} - ${cat.nombre}`;
                categorySelect.appendChild(option);
            });
        }
        
        updateUPCCode();
    });
    
    categorySelect.addEventListener('change', updateUPCCode);
    
    // Funcionalidad para agregar más selectores de archivos
    const addFileBtn = document.getElementById('addFileBtn');
    const fileInputsContainer = document.getElementById('fileInputsContainer');
    
    addFileBtn.addEventListener('click', function() {
        addFileInput();
    });
    
    // Funcionalidad para agregar más URLs de YouTube
    const addYoutubeUrlBtn = document.getElementById('addYoutubeUrlBtn');
    const youtubeUrlsContainer = document.getElementById('youtubeUrlsContainer');
    
    addYoutubeUrlBtn.addEventListener('click', function() {
        addYoutubeUrlInput();
    });
    
    // Inicializar eventos para vista previa en el primer input de archivo
    initializeFilePreview();
    
    // Validación de campos de texto
    const textInputs = document.querySelectorAll('input[type="text"]');
    textInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatTextInput(this);
        });
        
        // Aplicar formato al perder el foco también
        input.addEventListener('blur', function() {
            formatTextInput(this);
        });
    });
    
    // Manejo del envío del formulario
    const form = document.getElementById('productForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (validateForm()) {
            // En un caso real, aquí enviaríamos los datos al servidor
            alert('Formulario válido. En un caso real, los datos se enviarían al servidor.');
            // form.submit();
        }
    });
});

// Función para generar el código UPC
function updateUPCCode() {
    const division = document.getElementById('division').value;
    const department = document.getElementById('department').value;
    const category = document.getElementById('category').value;
    
    if (division && department && category) {
        // En un caso real, el contador vendría de la base de datos
        const countStr = String(productCount).padStart(5, '0');
        const upcCode = division + department + category + countStr;
        document.getElementById('upcCode').value = upcCode;
    } else {
        document.getElementById('upcCode').value = '';
    }
}

// Función para agregar un nuevo selector de archivos
function addFileInput() {
    const fileInputsContainer = document.getElementById('fileInputsContainer');
    const index = fileInputsContainer.children.length;
    
    const newFileInput = document.createElement('div');
    newFileInput.className = 'file-input-container';
    newFileInput.innerHTML = `
        <input type="file" name="productImages[]" accept="image/*" class="file-input">
        <div class="preview-container">
            <div class="image-preview">
                <div class="placeholder">Vista previa</div>
            </div>
            <label class="main-image-label">
                <input type="radio" name="mainImage" value="${index}" class="main-image-radio"> Imagen principal
            </label>
        </div>
        <button type="button" class="remove-file-btn">Eliminar</button>
    `;
    
    fileInputsContainer.appendChild(newFileInput);
    
    // Agregar funcionalidad al botón de eliminar
    const removeBtn = newFileInput.querySelector('.remove-file-btn');
    removeBtn.addEventListener('click', function() {
        fileInputsContainer.removeChild(newFileInput);
        // Reindexar los radio buttons de imagen principal
        reindexMainImageRadios();
    });
    
    // Inicializar vista previa para el nuevo input
    initializeFilePreviewForInput(newFileInput.querySelector('.file-input'));
}

// Función para inicializar la vista previa de imágenes
function initializeFilePreview() {
    const fileInputs = document.querySelectorAll('.file-input');
    fileInputs.forEach(input => {
        initializeFilePreviewForInput(input);
    });
}

// Función para inicializar la vista previa para un input específico
function initializeFilePreviewForInput(fileInput) {
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        const container = this.closest('.file-input-container');
        const preview = container.querySelector('.image-preview');
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            };
            
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '<div class="placeholder">Vista previa</div>';
        }
    });
}

// Función para reindexar los radio buttons de imagen principal
function reindexMainImageRadios() {
    const fileInputsContainer = document.getElementById('fileInputsContainer');
    const containers = fileInputsContainer.querySelectorAll('.file-input-container');
    
    containers.forEach((container, index) => {
        const radio = container.querySelector('.main-image-radio');
        radio.value = index;
    });
}

// Función para agregar un nuevo campo de URL de YouTube
function addYoutubeUrlInput() {
    const youtubeUrlsContainer = document.getElementById('youtubeUrlsContainer');
    
    const newUrlInput = document.createElement('div');
    newUrlInput.className = 'youtube-url-container';
    newUrlInput.innerHTML = `
        <input type="url" name="youtubeUrls[]" class="youtube-url-input" placeholder="https://www.youtube.com/watch?v=...">
        <button type="button" class="remove-youtube-btn">Eliminar</button>
    `;
    
    youtubeUrlsContainer.appendChild(newUrlInput);
    
    // Agregar funcionalidad al botón de eliminar
    const removeBtn = newUrlInput.querySelector('.remove-youtube-btn');
    removeBtn.addEventListener('click', function() {
        youtubeUrlsContainer.removeChild(newUrlInput);
    });
}

// Función para formatear campos de texto
function formatTextInput(input) {
    // Permitir solo letras, números, coma, punto y guion medio
    input.value = input.value.replace(/[^a-zA-Z0-9,.\-\s]/g, '');
    
    // Convertir primera letra a mayúscula y el resto a minúscula
    if (input.value.length > 0) {
        // Preservar espacios múltiples pero capitalizar cada palabra
        const words = input.value.split(' ');
        const formattedWords = words.map(word => {
            if (word.length > 0) {
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            }
            return '';
        });
        input.value = formattedWords.join(' ');
    }
}

// Función de validación del formulario
function validateForm() {
    let isValid = true;
    
    // Validar que al menos un archivo esté seleccionado
    const fileInputs = document.querySelectorAll('.file-input');
    let hasFiles = false;
    fileInputs.forEach(input => {
        if (input.files.length > 0) {
            hasFiles = true;
        }
    });
    
    if (!hasFiles) {
        document.getElementById('fileError').textContent = 'Debe seleccionar al menos una imagen';
        document.getElementById('fileError').style.display = 'block';
        isValid = false;
    } else {
        document.getElementById('fileError').style.display = 'none';
    }
    
    // Validar que al menos una imagen esté marcada como principal
    const mainImageSelected = document.querySelector('input[name="mainImage"]:checked');
    if (!mainImageSelected) {
        // Si hay imágenes pero ninguna está marcada como principal, marcar la primera automáticamente
        const firstRadio = document.querySelector('input[name="mainImage"]');
        if (firstRadio) {
            firstRadio.checked = true;
        }
    }
    
    // Validar URLs de YouTube (si existen)
    const youtubeInputs = document.querySelectorAll('.youtube-url-input');
    youtubeInputs.forEach(input => {
        if (input.value && !isValidYouTubeUrl(input.value)) {
            document.getElementById('youtubeUrlError').textContent = 'Una o más URLs de YouTube no son válidas';
            document.getElementById('youtubeUrlError').style.display = 'block';
            isValid = false;
        }
    });
    
    if (isValid) {
        document.getElementById('youtubeUrlError').style.display = 'none';
    }
    
    // Validar otros campos requeridos
    const requiredFields = [
        'description', 'standardPrice', 'retailUnits', 
        'wholesaleUnits', 'model', 'color', 'status',
        'division', 'department', 'category'
    ];
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + 'Error');
        
        if (!field.value) {
            errorElement.textContent = 'Este campo es obligatorio';
            errorElement.style.display = 'block';
            isValid = false;
        } else {
            errorElement.style.display = 'none';
        }
    });
    
    // Validaciones específicas
    const standardPrice = document.getElementById('standardPrice').value;
    if (standardPrice && parseFloat(standardPrice) <= 0) {
        document.getElementById('standardPriceError').textContent = 'El precio debe ser mayor a 0';
        document.getElementById('standardPriceError').style.display = 'block';
        isValid = false;
    }
    
    const status = document.getElementById('status').value;
    if (status && (parseInt(status) < 1 || parseInt(status) > 10)) {
        document.getElementById('statusError').textContent = 'El estado debe ser un número entre 1 y 10';
        document.getElementById('statusError').style.display = 'block';
        isValid = false;
    }
    
    return isValid;
}

// Función para validar URLs de YouTube
function isValidYouTubeUrl(url) {
    const regex = /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+$/;
    return regex.test(url);
}