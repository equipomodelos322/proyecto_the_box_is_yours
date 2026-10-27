// ========== VARIABLES GLOBALES ==========
let todosProductos = [];
let productosFiltrados = [];
let paginaActual = 1;
let productosPorPagina = 12;
let categoriaFiltro = '';
let marcaFiltro = '';
let precioMin = 0;
let precioMax = 999999;
let ordenActual = 'nombre_asc';
let busquedaActual = '';
let productoActual = null;
let variantesActuales = [];
let varianteSeleccionada = null;

// ========== CARGAR PRODUCTOS ==========
async function cargarProductos() {
    try {
        let response = await fetch('get_productos.php');
        let data = await response.json();
        todosProductos = data;
        aplicarFiltros();
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('productos-container').innerHTML = '<p style="text-align:center;">Error al cargar productos</p>';
    }
}

function aplicarFiltros() {
    productosFiltrados = todosProductos.filter(prod => {
        if (categoriaFiltro && prod.Categoria !== categoriaFiltro) return false;
        if (marcaFiltro && !prod.nombre_base.toUpperCase().includes(marcaFiltro.toUpperCase())) return false;
        let precioMedio = prod.precio_min ? prod.precio_min : 0;
        if (precioMedio < precioMin || precioMedio > precioMax) return false;
        if (busquedaActual && !prod.nombre_base.toUpperCase().includes(busquedaActual.toUpperCase())) return false;
        return true;
    });
    aplicarOrden();
}

function aplicarOrden() {
    switch(ordenActual) {
        case 'precio_asc': productosFiltrados.sort((a,b) => (a.precio_min||0) - (b.precio_min||0)); break;
        case 'precio_desc': productosFiltrados.sort((a,b) => (b.precio_min||0) - (a.precio_min||0)); break;
        case 'nombre_asc': productosFiltrados.sort((a,b) => a.nombre_base.localeCompare(b.nombre_base)); break;
        default: productosFiltrados.sort((a,b) => a.nombre_base.localeCompare(b.nombre_base));
    }
    paginaActual = 1;
    mostrarProductos();
}

function mostrarProductos() {
    let start = (paginaActual - 1) * productosPorPagina;
    let end = start + productosPorPagina;
    let productosPagina = productosFiltrados.slice(start, end);
    
    let container = document.getElementById('productos-container');
    let resultadosCount = document.getElementById('resultados-count');
    
    if(productosFiltrados.length === 0) {
        resultadosCount.innerHTML = 'Mostrando 0 productos';
        container.innerHTML = '<p style="text-align:center; padding:3rem;">No se encontraron productos</p>';
        document.getElementById('paginacion').innerHTML = '';
        return;
    }
    
    resultadosCount.innerHTML = `Mostrando ${start+1}-${Math.min(end, productosFiltrados.length)} de ${productosFiltrados.length} resultados`;
    
    let html = '<div class="productos-grid">';
    for(let i = 0; i < productosPagina.length; i++) {
        let prod = productosPagina[i];
        let precioTexto = '';
        if (prod.precio_min && prod.precio_max && prod.precio_min != prod.precio_max) {
            precioTexto = `$${prod.precio_min.toLocaleString()} - $${prod.precio_max.toLocaleString()}`;
        } else if (prod.precio_min) {
            precioTexto = `$${prod.precio_min.toLocaleString()}`;
        } else {
            precioTexto = 'Consultar';
        }
        
        let imagenHtml = prod.imagen_principal 
            ? `<img src="${prod.imagen_principal}" class="producto-imagen" alt="${prod.nombre_base}" onerror="this.outerHTML='<div class=\\'producto-imagen\\'>🥊</div>'">` 
            : '<div class="producto-imagen">🥊</div>';
        
        let stockClass = '', stockText = '';
        let stock = prod.stock_total || 0;
        if(stock <= 0) {
            stockClass = 'stock-agotado'; 
            stockText = '❌ Agotado';
        } else if(stock <= 5) {
            stockClass = 'stock-poco'; 
            stockText = `⚠️ Últimas ${stock}`;
        } else {
            stockClass = 'stock-disponible'; 
            stockText = `✅ ${stock} disponibles`;
        }
        
        html += `<div class="producto-card">${imagenHtml}<div class="producto-info">`;
        html += `<div class="producto-titulo">${prod.nombre_base}</div>`;
        html += `<div class="producto-categoria">${prod.Categoria || 'Sin categoría'}</div>`;
        html += `<div class="producto-precio">${precioTexto} <span>MXN</span></div>`;
        html += `<div class="producto-stock ${stockClass}">${stockText}</div>`;
        html += `<button class="btn-ver-detalles" onclick="verDetalles(${prod.id})">🔍 Ver detalles</button>`;
        html += `</div></div>`;
    }
    html += '</div>';
    container.innerHTML = html;
    
    let totalPaginas = Math.ceil(productosFiltrados.length / productosPorPagina);
    let paginacionHtml = '';
    
    if(totalPaginas > 1) {
        if(paginaActual > 1) {
            paginacionHtml += `<button onclick="irPagina(${paginaActual - 1})">← Anterior</button>`;
        }
        
        for(let i = 1; i <= totalPaginas; i++) {
            if(i === 1 || i === totalPaginas || (i >= paginaActual - 1 && i <= paginaActual + 1)) {
                paginacionHtml += `<button class="${i === paginaActual ? 'active' : ''}" onclick="irPagina(${i})">${i}</button>`;
            } else if(i === paginaActual - 2 || i === paginaActual + 2) {
                paginacionHtml += `<span style="color:#64748b; padding:0.5rem;">...</span>`;
            }
        }
        
        if(paginaActual < totalPaginas) {
            paginacionHtml += `<button onclick="irPagina(${paginaActual + 1})">Siguiente →</button>`;
        }
    }
    
    document.getElementById('paginacion').innerHTML = paginacionHtml;
}

// ========== CONTROLES ==========
function irPagina(pagina) { 
    paginaActual = pagina; 
    mostrarProductos(); 
    document.getElementById('productos').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function filtrarCategoria(catNombre) { 
    categoriaFiltro = catNombre; 
    marcaFiltro = ''; 
    precioMin = 0; 
    precioMax = 999999; 
    busquedaActual = ''; 
    document.getElementById('searchInput').value = ''; 
    aplicarFiltros(); 
}

function filtrarMarca(marca) { 
    marcaFiltro = marca; 
    categoriaFiltro = ''; 
    aplicarFiltros(); 
}

function filtrarPrecio(min, max) { 
    precioMin = min; 
    precioMax = max; 
    categoriaFiltro = ''; 
    marcaFiltro = ''; 
    aplicarFiltros(); 
}

function cargarTodos() { 
    categoriaFiltro = ''; 
    marcaFiltro = ''; 
    precioMin = 0; 
    precioMax = 999999; 
    busquedaActual = ''; 
    document.getElementById('searchInput').value = ''; 
    aplicarFiltros(); 
}

function buscarProducto() { 
    busquedaActual = document.getElementById('searchInput').value; 
    categoriaFiltro = ''; 
    marcaFiltro = ''; 
    precioMin = 0; 
    precioMax = 999999; 
    aplicarFiltros(); 
}

function ordenarProductos() { 
    ordenActual = document.getElementById('ordenSelect').value; 
    aplicarOrden(); 
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') buscarProducto();
});

// ========== NOTIFICACIONES ==========
function mostrarNotificacion(mensaje, tipo = 'success') { 
    let n = document.createElement('div'); 
    n.className = 'notification'; 
    n.style.background = tipo === 'error' ? '#ef4444' : (tipo === 'warning' ? '#eab308' : '#22c55e');
    n.innerHTML = mensaje; 
    document.body.appendChild(n); 
    setTimeout(() => {
        n.style.opacity = '0';
        n.style.transition = '0.3s';
        setTimeout(() => n.remove(), 300);
    }, 3000); 
}

// ========== CERRAR MODAL ==========
function cerrarModal() { 
    document.getElementById('productModal').style.display = 'none'; 
    document.body.style.overflow = '';
}

// ========== CONTADOR CARRITO ==========
function actualizarContadorCarrito() { 
    fetch('cart.php?action=get')
        .then(res => res.json())
        .then(data => { 
            let cartSpan = document.getElementById('cartCount');
            if(cartSpan) cartSpan.innerHTML = data.total_items || 0;
        })
        .catch(e => console.error('Error contador:', e));
}

// ========== WHATSAPP ==========
function consultarWhatsApp(nombre, precio) {
    const numero = '5215564442192';
    const mensaje = encodeURIComponent(`Hola, me interesa ${nombre} - $${precio} MXN`);
    window.open(`https://wa.me/${numero}?text=${mensaje}`, '_blank');
}

// ========== COLORES ==========
function getColorValue(color) {
    const colores = {
        'Rojo': '#ef4444', 'Azul': '#3b82f6', 'Negro': '#1e293b',
        'Blanco': '#f8fafc', 'Gris': '#64748b', 'Verde': '#22c55e',
        'Amarillo': '#eab308', 'Morado': '#a855f7', 'Naranja': '#f97316',
        'Rosa': '#ec4899', 'Café': '#78350f', 'Dorado': '#fbbf24',
        'Plateado': '#94a3b8', 'Beige': '#f5f5dc', 'Marino': '#1e3a8a',
        'Celeste': '#38bdf8'
    };
    return colores[color] || '#64748b';
}

// ========== VER DETALLES ==========
async function verDetalles(idProductoBase) {
    try {
        let response = await fetch('get_producto_detalle.php?id=' + idProductoBase + '&t=' + Date.now());
        let prod = await response.json();
        
        if (prod.error) {
            mostrarNotificacion('❌ ' + prod.error, 'error');
            return;
        }
        
        productoActual = prod;
        variantesActuales = prod.variantes;
        varianteSeleccionada = null;
        
        let imagenPrincipal = prod.imagen_principal || 'uploads/default.jpg';
        
        let tallas = prod.tallas_disponibles || [];
        let onzas = prod.onzas_disponibles || [];
        let colores = prod.colores_disponibles || [];
        
        // Colores
        let coloresHtml = '';
        if (colores.length > 0) {
            coloresHtml = `<div class="variantes-group">
                <label>🎨 Selecciona un Color</label>
                <div class="color-selector" id="color-selector">`;
            
            colores.forEach(color => {
                let bgColor = getColorValue(color);
                coloresHtml += `
                    <div class="color-option" data-color="${color}" onclick="seleccionarColor('${color}')">
                        <div class="color-circle" style="background-color: ${bgColor}; ${color === 'Blanco' ? 'border-color:#64748b;' : ''}"></div>
                        <span class="color-name">${color}</span>
                    </div>
                `;
            });
            
            coloresHtml += `</div></div>`;
        }
        
        // Talla y Onza
        let atributosHtml = '';
        
        if (tallas.length > 0) {
            atributosHtml += `<div class="variantes-group" id="talla-group" style="display: none;">
                <label>📏 Talla</label>
                <select id="select_talla" onchange="actualizarVarianteSeleccionada()">
                    <option value="">-- Selecciona --</option>`;
            tallas.forEach(t => atributosHtml += `<option value="${t}">${t}</option>`);
            atributosHtml += `</select></div>`;
        }
        
        if (onzas.length > 0) {
            atributosHtml += `<div class="variantes-group" id="onza-group" style="display: none;">
                <label>⚖️ Onza</label>
                <select id="select_onza" onchange="actualizarVarianteSeleccionada()">
                    <option value="">-- Selecciona --</option>`;
            onzas.forEach(o => atributosHtml += `<option value="${o}">${o}</option>`);
            atributosHtml += `</select></div>`;
        }
        
        let stockHtml = `<div id="stock_info" class="stock-info" style="background:rgba(100,116,139,0.1); color:#94a3b8;">⚠️ Selecciona las opciones</div>`;
        let cantidadHtml = `<div class="cantidad-group">
            <label>🔢 Cantidad:</label>
            <input type="number" id="cantidad_input" value="1" min="1" max="0" disabled>
        </div>`;
        let precioHtml = `<div class="modal-precio">💰 $<span id="precio_variante">0.00</span> MXN</div>`;
        let botonesHtml = `
            <div class="modal-buttons">
                <button class="btn-comprar" id="btn_comprar" disabled style="opacity:0.5;">🛒 Agregar al carrito</button>
                <button class="btn-whatsapp" onclick="consultarWhatsApp('${prod.nombre_base.replace(/'/g, "\\'")}', '0')">💬 WhatsApp</button>
            </div>
        `;
        
        let modalBody = `
            <div class="modal-producto">
                <div class="modal-imagen">
                    <img src="${imagenPrincipal}" id="productoImagenModal" onerror="this.src='uploads/default.jpg'">
                </div>
                <div class="modal-info">
                    <h2>${prod.nombre_base}</h2>
                    <div class="modal-categoria">📂 ${prod.Categoria || 'Sin categoría'}</div>
                    ${precioHtml}
                    <div class="modal-descripcion">📝 ${prod.descripcion || 'Sin descripción'}</div>
                    ${coloresHtml}
                    ${atributosHtml}
                    ${stockHtml}
                    ${cantidadHtml}
                    ${botonesHtml}
                </div>
            </div>
        `;
        
        document.getElementById('modalBody').innerHTML = modalBody;
        document.getElementById('productModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        window.variantesActuales = variantesActuales;
        window.colorSeleccionado = null;
        
        const btn = document.getElementById('btn_comprar');
        if (btn) btn.onclick = agregarVarianteAlCarrito;
        
        // Recomendaciones
        cargarRecomendaciones(idProductoBase);
        
    } catch(e) {
        console.error('Error:', e);
        mostrarNotificacion('❌ Error al cargar los detalles', 'error');
    }
}

// ========== SELECCIONAR COLOR ==========
function seleccionarColor(color) {
    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
    
    const selectedOption = document.querySelector(`.color-option[data-color="${color}"]`);
    if (selectedOption) selectedOption.classList.add('selected');
    
    window.colorSeleccionado = color;
    
    const tallaGroup = document.getElementById('talla-group');
    const onzaGroup = document.getElementById('onza-group');
    
    let tieneTalla = false;
    let tieneOnza = false;
    
    window.variantesActuales.forEach(v => {
        if (v.color === color) {
            if (v.talla && v.talla !== '') tieneTalla = true;
            if (v.onza && v.onza !== '') tieneOnza = true;
        }
    });
    
    if (tallaGroup) {
        tallaGroup.style.display = tieneTalla ? 'block' : 'none';
        if (tieneTalla) {
            let tallasDisp = [];
            window.variantesActuales.forEach(v => {
                if (v.color === color && v.talla && !tallasDisp.includes(v.talla)) tallasDisp.push(v.talla);
            });
            const selectTalla = document.getElementById('select_talla');
            if (selectTalla) {
                selectTalla.innerHTML = '<option value="">-- Selecciona --</option>';
                tallasDisp.forEach(t => selectTalla.innerHTML += `<option value="${t}">${t}</option>`);
            }
        }
    }
    
    if (onzaGroup) {
        onzaGroup.style.display = tieneOnza ? 'block' : 'none';
        if (tieneOnza) {
            let onzasDisp = [];
            window.variantesActuales.forEach(v => {
                if (v.color === color && v.onza && !onzasDisp.includes(v.onza)) onzasDisp.push(v.onza);
            });
            const selectOnza = document.getElementById('select_onza');
            if (selectOnza) {
                selectOnza.innerHTML = '<option value="">-- Selecciona --</option>';
                onzasDisp.forEach(o => selectOnza.innerHTML += `<option value="${o}">${o}</option>`);
            }
        }
    }
    
    actualizarVarianteSeleccionada();
}

// ========== ACTUALIZAR VARIANTE ==========
function actualizarVarianteSeleccionada() {
    let colorSel = window.colorSeleccionado || '';
    let tallaSel = document.getElementById('select_talla')?.value || '';
    let onzaSel = document.getElementById('select_onza')?.value || '';
    
    const cantidadInput = document.getElementById('cantidad_input');
    const btnComprar = document.getElementById('btn_comprar');
    const precioSpan = document.getElementById('precio_variante');
    const stockInfoDiv = document.getElementById('stock_info');
    const imagenModal = document.getElementById('productoImagenModal');
    const imagenPrincipal = productoActual?.imagen_principal || 'uploads/default.jpg';
    
    const variante = window.variantesActuales?.find(v => 
        (v.color || '') === colorSel && 
        (v.talla || '') === tallaSel && 
        (v.onza || '') === onzaSel
    );
    
    if (variante && variante.stock > 0) {
        window.varianteSeleccionada = variante;
        precioSpan.innerText = parseFloat(variante.precio).toLocaleString('es-MX', {minimumFractionDigits: 2});
        
        if (variante.stock <= 5) {
            stockInfoDiv.innerHTML = `⚠️ Últimas ${variante.stock} unidades`;
            stockInfoDiv.style.background = 'rgba(234,179,8,0.15)';
            stockInfoDiv.style.color = '#eab308';
        } else {
            stockInfoDiv.innerHTML = `✅ ${variante.stock} unidades disponibles`;
            stockInfoDiv.style.background = 'rgba(34,197,94,0.15)';
            stockInfoDiv.style.color = '#22c55e';
        }
        
        cantidadInput.disabled = false;
        cantidadInput.max = variante.stock;
        cantidadInput.value = 1;
        btnComprar.disabled = false;
        btnComprar.style.opacity = '1';
        
        imagenModal.src = variante.imagen || imagenPrincipal;
        
        cantidadInput.oninput = function() {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) val = 1;
            if (val > variante.stock) val = variante.stock;
            this.value = val;
        };
        
    } else if (variante && variante.stock <= 0) {
        window.varianteSeleccionada = null;
        precioSpan.innerText = '0.00';
        stockInfoDiv.innerHTML = '❌ Producto agotado';
        stockInfoDiv.style.background = 'rgba(239,68,68,0.15)';
        stockInfoDiv.style.color = '#ef4444';
        cantidadInput.disabled = true;
        btnComprar.disabled = true;
        btnComprar.style.opacity = '0.5';
        cantidadInput.value = 0;
    } else {
        window.varianteSeleccionada = null;
        precioSpan.innerText = '0.00';
        stockInfoDiv.innerHTML = colorSel ? '⚠️ Selecciona todas las opciones' : '⚠️ Selecciona un color';
        stockInfoDiv.style.background = 'rgba(100,116,139,0.1)';
        stockInfoDiv.style.color = '#94a3b8';
        cantidadInput.disabled = true;
        btnComprar.disabled = true;
        btnComprar.style.opacity = '0.5';
        cantidadInput.value = 0;
    }
}

// ========== AGREGAR AL CARRITO ==========
async function agregarVarianteAlCarrito() {
    <?php if(!isset($_SESSION['user_id'])): ?>
        mostrarNotificacion('🔐 Inicia sesión para comprar', 'warning');
        setTimeout(() => window.location.href = 'login.php', 1200);
        return;
    <?php endif; ?>
    
    if (!window.varianteSeleccionada) {
        mostrarNotificacion('⚠️ Selecciona todas las opciones', 'warning');
        return;
    }
    
    let cantidadInput = document.getElementById('cantidad_input');
    let cantidad = parseInt(cantidadInput.value);
    if (isNaN(cantidad) || cantidad < 1) cantidad = 1;
    
    if (cantidad > window.varianteSeleccionada.stock) {
        mostrarNotificacion(`⚠️ Solo hay ${window.varianteSeleccionada.stock} unidades`, 'warning');
        return;
    }
    
    let nombreCompleto = productoActual.nombre_base;
    if (window.varianteSeleccionada.color) nombreCompleto += ` - ${window.varianteSeleccionada.color}`;
    if (window.varianteSeleccionada.talla) nombreCompleto += ` (Talla ${window.varianteSeleccionada.talla})`;
    if (window.varianteSeleccionada.onza) nombreCompleto += ` (${window.varianteSeleccionada.onza})`;
    
    let formData = new FormData();
    formData.append('action', 'add');
    formData.append('variante_id', window.varianteSeleccionada.id);
    formData.append('nombre', nombreCompleto);
    formData.append('precio', window.varianteSeleccionada.precio);
    formData.append('imagen', window.varianteSeleccionada.imagen || '');
    formData.append('cantidad', cantidad);
    
    try {
        let response = await fetch('cart.php', { method: 'POST', body: formData });
        let data = await response.json();
        
        if (data.success) {
            mostrarNotificacion(`✅ Agregado al carrito`, 'success');
            actualizarContadorCarrito();
            cerrarModal();
        } else {
            if(data.redirect) {
                mostrarNotificacion('🔐 ' + data.error, 'warning');
                setTimeout(() => window.location.href = data.redirect, 1200);
            } else {
                mostrarNotificacion(`❌ ${data.error || 'No se pudo agregar'}`, 'error');
            }
        }
    } catch (error) {
        mostrarNotificacion('❌ Error al conectar con el servidor', 'error');
    }
}

// ========== RECOMENDACIONES ==========
async function cargarRecomendaciones(idActual) {
    try {
        let res = await fetch('get_recomendaciones.php?id=' + idActual);
        let recs = await res.json();
        
        if(!recs || recs.length === 0) return;
        
        let html = '<div style="margin-top:2rem; border-top:1px solid #1e293b; padding-top:1.5rem;">';
        html += '<h3 style="color:#00b4d8; margin-bottom:1rem;">🔥 También te puede interesar</h3>';
        html += '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px,1fr)); gap:1rem;">';
        
        recs.forEach(r => {
            let img = r.imagen_principal || 'uploads/default.jpg';
            let precio = r.precio_min ? '$' + parseFloat(r.precio_min).toLocaleString('es-MX') : 'Consultar';
            html += `
                <div style="background:#020617; border:1px solid #1e293b; border-radius:8px; overflow:hidden; cursor:pointer; transition:0.3s;"
                     onclick="cerrarModal(); setTimeout(() => verDetalles(${r.id}), 300);"
                     onmouseover="this.style.borderColor='#00b4d8'; this.style.transform='translateY(-3px)';"
                     onmouseout="this.style.borderColor='#1e293b'; this.style.transform='translateY(0)';">
                    <img src="${img}" style="width:100%; height:120px; object-fit:cover;" onerror="this.src='uploads/default.jpg'">
                    <div style="padding:0.6rem;">
                        <div style="font-size:0.8rem; color:white; font-weight:bold; margin-bottom:0.3rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            ${r.nombre_base}
                        </div>
                        <div style="font-size:0.75rem; color:#22c55e; font-weight:bold;">${precio} MXN</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div></div>';
        
        let modalInfo = document.querySelector('.modal-info');
        if(modalInfo) modalInfo.insertAdjacentHTML('beforeend', html);
    } catch(e) {
        console.error('Error recomendaciones:', e);
    }
}

// ========== ESC Y CLICK FUERA ==========
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        let modal = document.getElementById('productModal');
        if(modal.style.display === 'block') cerrarModal();
    }
});

window.onclick = function(e) {
    let modal = document.getElementById('productModal');
    if(e.target == modal) cerrarModal();
};

// ========== INICIALIZACIÓN ==========
cargarProductos();
actualizarContadorCarrito();
</script>