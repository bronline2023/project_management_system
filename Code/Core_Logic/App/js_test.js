
    // --- System Context ---
    const userRole = "<?= $user_role ?>";
    const cardCost = <?= number_format($card_cost, 2, '.', '') ?>;
    const currency = "<?= $currency ?>";
    const baseUrl = "<?= BASE_URL ?>"; 

    // CRITICAL: Prevent WebGL "White Band" bugs on large image filters (Forces CPU Rendering)
    fabric.filterBackend = new fabric.Canvas2dFilterBackend();

    // --- Global Canvas Variables ---
    let canvas;
    let LOGICAL_W = 1080; // Default High Res Canvas
    let LOGICAL_H = 1080;
    
    // --- Tool States ---
    let cropZone = null;
    let isMasking = false;
    let maskPathHistory = []; // Stores drawn masks for object removal

    // ==========================================
    // 1. INITIALIZATION & RESIZE HANDLING
    // ==========================================
    window.onload = function() {
        // Initialize Fabric Canvas
        canvas = new fabric.Canvas('mainCanvas', {
            width: LOGICAL_W, 
            height: LOGICAL_H, 
            backgroundColor: '#ffffff',
            preserveObjectStacking: true // Critical for layers
        });

        // Setup responsive scaling (Zoom)
        fitCanvasToScreen();
        window.addEventListener('resize', fitCanvasToScreen);

        // Attach Event Listeners for UI updates
        canvas.on('selection:created', handleSelection);
        canvas.on('selection:updated', handleSelection);
        canvas.on('selection:cleared', handleDeselection);
        
        // Save brush paths if masking for Object Removal
        canvas.on('path:created', function(e) {
            if(isMasking) {
                e.path.set({ 
                    isMask: true, 
                    selectable: false, 
                    evented: false,
                    opacity: 0.6 // Semi-transparent so user can see what they painted over
                });
                maskPathHistory.push(e.path);
            }
        });

        // 🚀 PREVENT TEXT SCALING DISTORTION 🚀
        canvas.on('object:scaling', function(e) {
            const obj = e.target;
            if (obj.type === 'i-text') {
                let newSize = obj.fontSize * obj.scaleX;
                obj.set({ fontSize: newSize, scaleX: 1, scaleY: 1 });
                
                if(canvas.getActiveObject() === obj) {
                    let safeSize = Math.round(newSize);
                    document.getElementById('fontSizeSlider').value = safeSize;
                    document.getElementById('valFontSize').innerText = safeSize;
                }
                obj.setCoords(); 
            }
        });

        // Keyboard Delete
        window.addEventListener('keydown', function(e) {
            if(e.key === 'Delete' || e.key === 'Backspace') {
                if(e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                const activeObj = canvas.getActiveObject();
                if(activeObj && activeObj.isEditing) return; 
                deleteSelected();
            }
        });
    };

    function fitCanvasToScreen() {
        const workspace = document.getElementById('workspaceContainer');
        if(!workspace) return;
        
        const padding = 100; // Leave space around canvas
        
        let scaleX = (workspace.clientWidth - padding) / LOGICAL_W;
        let scaleY = (workspace.clientHeight - padding) / LOGICAL_H;
        let scale = Math.min(scaleX, scaleY);
        
        if(scale > 1) scale = 1;

        canvas.setZoom(scale);
        canvas.setDimensions({ 
            width: LOGICAL_W * scale, 
            height: LOGICAL_H * scale 
        });
    }

    function showLoading(show, text = 'Processing...') {
        document.getElementById('loadingText').innerHTML = text;
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }

    // ==========================================
    // 2. PANEL & UI ROUTING (SAFE MODE)
    // ==========================================
    function openPanel(panelId, eventObj = null) {
        try {
            document.querySelectorAll('.props-sidebar').forEach(el => el.style.display = 'none');
            const targetPanel = document.getElementById(panelId);
            if (targetPanel) targetPanel.style.display = 'block';

            document.querySelectorAll('.tool-icon-btn').forEach(el => el.classList.remove('active'));
            
            if (eventObj && eventObj.currentTarget) {
                eventObj.currentTarget.classList.add('active');
            } else {
                document.querySelectorAll('.tool-icon-btn').forEach(btn => {
                    const onclickAttr = btn.getAttribute('onclick');
                    if(onclickAttr && onclickAttr.includes("openPanel('"+panelId+"'")) btn.classList.add('active');
                });
            }

            // --- Cleanup conflicting modes ---
            if(panelId !== 'panel-draw') {
                canvas.isDrawingMode = false;
                document.getElementById('btnDrawMode').innerHTML = '<i class="fas fa-pen"></i> Start Drawing';
                document.getElementById('btnDrawMode').style.background = '#10b981';
            }
            if(panelId !== 'panel-ai-pro') {
                if(isMasking) toggleEraserMask(); 
            }
            if(panelId !== 'panel-crop' && cropZone) {
                cancelCrop(); 
            }
            
            // --- Sync specific UI ---
            if (panelId === 'panel-text') syncTextPanel();
            if (panelId === 'panel-filter' || panelId === 'panel-shadow') syncEffectsPanel();
            
        } catch (e) { console.error("Panel Error: ", e); }
    }

    function handleSelection(e) {
        const activeObj = e.selected[0];
        if(!activeObj) return;

        if (cropZone && activeObj !== cropZone) { 
            canvas.setActiveObject(cropZone); 
            return; 
        }
        if (activeObj.isGrid) { 
            canvas.discardActiveObject(); 
            return; 
        }

        if(activeObj.type === 'image') {
            let currentPanelId = '';
            document.querySelectorAll('.props-sidebar').forEach(el => {
                if(el.style.display === 'block') currentPanelId = el.id;
            });
            
            if(!['panel-ai-pro', 'panel-bg-remove', 'panel-crop', 'panel-collage', 'panel-shadow'].includes(currentPanelId)) {
                openPanel('panel-filter');
            }
            syncEffectsPanel();
            
        } else if (activeObj.type === 'i-text') {
            openPanel('panel-text');
            syncTextPanel();
            syncEffectsPanel(); // Texts can have shadows too
            
        } else if (['rect', 'circle', 'triangle', 'polygon', 'line'].includes(activeObj.type)) {
            openPanel('panel-shape');
            let colorToSet = activeObj.fill;
            if(activeObj.type === 'line' || activeObj.fill === 'transparent') colorToSet = activeObj.stroke;
            document.getElementById('shapeColorPicker').value = colorToSet;
            syncEffectsPanel();
        }
    }

    function handleDeselection() {
        document.querySelectorAll('.format-btn').forEach(btn => btn.classList.remove('active')); 
    }

    // ==========================================
    // 3. MULTI-IMAGE UPLOAD (COLLAGE ENABLED)
    // ==========================================
    function handleMultiImageUpload(event) {
        const files = event.target.files; 
        if (!files || files.length === 0) return;
        
        showLoading(true, "Adding Image(s)...");
        let loadedCount = 0; const totalFiles = files.length;

        for(let i=0; i<totalFiles; i++) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    fabric.Image.fromURL(e.target.result, function(img) {
                        if (img) {
                            let scale = 1; 
                            if(img.width > LOGICAL_W || img.height > LOGICAL_H) {
                                scale = Math.min(LOGICAL_W / img.width, LOGICAL_H / img.height) * 0.8;
                            }
                            let offset = loadedCount * 40; 

                            img.set({ 
                                left: (LOGICAL_W / 2) + offset, top: (LOGICAL_H / 2) + offset, 
                                originX: 'center', originY: 'center', scaleX: scale, scaleY: scale, 
                                cornerColor: '#38bdf8', borderColor: '#38bdf8', transparentCorners: false 
                            });
                            
                            img.filters = []; 
                            canvas.add(img); canvas.setActiveObject(img); 
                        }
                        loadedCount++;
                        if(loadedCount === totalFiles) {
                            try { openPanel('panel-filter'); } catch(err) {}
                            canvas.renderAll(); showLoading(false);
                        }
                    }, { crossOrigin: 'anonymous' }); 
                } catch(err) {
                    console.error(err); loadedCount++;
                    if(loadedCount === totalFiles) showLoading(false);
                }
            };
            reader.onerror = function() { loadedCount++; if(loadedCount === totalFiles) showLoading(false); };
            reader.readAsDataURL(files[i]); 
        }
        event.target.value = ''; 
    }

    // ==========================================
    // 4. CANVAS SETUP & COLLAGE GRIDS
    // ==========================================
    function applyResize() {
        const w = parseInt(document.getElementById('resizeW').value); 
        const h = parseInt(document.getElementById('resizeH').value);
        if(!w || !h || w < 100 || h < 100) return;
        
        showLoading(true, "Resizing Canvas...");
        setTimeout(() => { 
            LOGICAL_W = w; 
            LOGICAL_H = h; 
            fitCanvasToScreen(); 
            showLoading(false); 
        }, 100);
    }
    
    function changeCanvasBg() { 
        canvas.backgroundColor = document.getElementById('bgColorPicker').value; 
        canvas.renderAll(); 
    }
    
    function setBgColorDirect(hex) { 
        document.getElementById('bgColorPicker').value = hex === 'transparent' ? '#ffffff' : hex; 
        canvas.backgroundColor = hex === 'transparent' ? null : hex; 
        canvas.renderAll(); 
    }

    function clearCanvas() { 
        if(confirm("Are you sure you want to clear EVERYTHING on the canvas?")) { 
            canvas.clear(); 
            maskPathHistory = []; 
            canvas.backgroundColor = '#ffffff'; 
            document.getElementById('bgColorPicker').value = '#ffffff';
            canvas.renderAll(); 
        } 
    }

    function addCollageGrid(count, type) {
        let bColor = document.getElementById('gridBorderColor').value || '#ffffff';
        let bWidth = parseInt(document.getElementById('gridBorder').value) || 10;

        let lines = [];
        let common = { stroke: bColor, strokeWidth: bWidth, selectable: false, evented: false, isGrid: true };

        if(count === 2 && type === 'vertical') {
            lines.push(new fabric.Line([LOGICAL_W/2, 0, LOGICAL_W/2, LOGICAL_H], common));
        } else if(count === 2 && type === 'horizontal') {
            lines.push(new fabric.Line([0, LOGICAL_H/2, LOGICAL_W, LOGICAL_H/2], common));
        } else if(count === 3 && type === 'vertical') {
            lines.push(new fabric.Line([LOGICAL_W/3, 0, LOGICAL_W/3, LOGICAL_H], common));
            lines.push(new fabric.Line([(LOGICAL_W/3)*2, 0, (LOGICAL_W/3)*2, LOGICAL_H], common));
        } else if(count === 4 && type === 'grid') {
            lines.push(new fabric.Line([LOGICAL_W/2, 0, LOGICAL_W/2, LOGICAL_H], common));
            lines.push(new fabric.Line([0, LOGICAL_H/2, LOGICAL_W, LOGICAL_H/2], common));
        }

        let frame = new fabric.Rect({
            left: 0, top: 0, width: LOGICAL_W, height: LOGICAL_H,
            fill: 'transparent', stroke: bColor, strokeWidth: bWidth*2,
            selectable: false, evented: false, isGrid: true
        });

        lines.forEach(l => { canvas.add(l); canvas.bringToFront(l); });
        canvas.add(frame); canvas.bringToFront(frame);
        canvas.renderAll();
    }

    function updateGridBorders() {
        let bColor = document.getElementById('gridBorderColor').value;
        let bWidth = parseInt(document.getElementById('gridBorder').value);
        canvas.getObjects().forEach(obj => {
            if(obj.isGrid) {
                obj.set({ stroke: bColor, strokeWidth: obj.type === 'rect' ? bWidth*2 : bWidth });
            }
        });
        canvas.renderAll();
    }

    // ==========================================
    // 5. LAYER MANAGEMENT
    // ==========================================
    function deleteSelected() {
        const activeObjs = canvas.getActiveObjects();
        if (activeObjs.length) { 
            activeObjs.forEach(obj => canvas.remove(obj)); 
            canvas.discardActiveObject(); 
            canvas.renderAll(); 
        }
    }
    function bringForward() { const obj = canvas.getActiveObject(); if(obj) { canvas.bringForward(obj); canvas.renderAll(); } }
    function sendBackward() { const obj = canvas.getActiveObject(); if(obj) { canvas.sendBackwards(obj); canvas.renderAll(); } }
    function flipObject(dir) { const obj = canvas.getActiveObject(); if(obj) { if(dir === 'x') obj.set('flipX', !obj.flipX); if(dir === 'y') obj.set('flipY', !obj.flipY); canvas.renderAll(); } }

    // ==========================================
    // 6. EXACT CROP TOOL (100% MATH FIXED)
    // ==========================================
    function startCrop() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') { alert("Please select photo to crop."); return; }

        document.getElementById('btnStartCrop').style.display = 'none'; 
        document.getElementById('cropOptions').style.display = 'block';
        
        let cWidth = obj.getScaledWidth() * 0.8; 
        let cHeight = obj.getScaledHeight() * 0.8;

        cropZone = new fabric.Rect({
            fill: 'rgba(0,0,0,0.5)', borderColor: '#10b981', cornerColor: '#10b981', cornerSize: 12, transparentCorners: false,
            left: obj.left, top: obj.top, width: cWidth, height: cHeight, 
            originX: 'center', originY: 'center', strokeDashArray: [5, 5], stroke: '#ffffff', strokeWidth: 2, lockRotation: true
        });
        
        canvas.add(cropZone); canvas.bringToFront(cropZone); canvas.setActiveObject(cropZone); canvas.renderAll();
    }

    function setCropRatio(ratio) {
        if(!cropZone) return;
        
        if(ratio === 'free') { 
            cropZone.set({ lockUniScaling: false }); 
        } else {
            let parts = ratio.split(':'); let wRatio = parseInt(parts[0]); let hRatio = parseInt(parts[1]);
            
            let currentWidth = cropZone.width * cropZone.scaleX; 
            let newHeight = currentWidth / (wRatio / hRatio);
            
            cropZone.set({ 
                width: wRatio * 100, height: hRatio * 100, 
                scaleX: currentWidth / (wRatio * 100), scaleY: newHeight / (hRatio * 100), 
                lockUniScaling: true 
            });
        }
        canvas.renderAll();
    }

    function applyCrop() {
        if(!cropZone) return;
        
        showLoading(true, "Cropping perfectly...");

        setTimeout(() => {
            // Get exact logical coordinates (ignores zoom factor)
            let rect = cropZone.getBoundingRect();
            let zoom = canvas.getZoom();
            
            let lLeft = rect.left / zoom; 
            let lTop = rect.top / zoom;
            let lWidth = rect.width / zoom; 
            let lHeight = rect.height / zoom;

            // Hide crop box
            cropZone.visible = false; 
            canvas.discardActiveObject(); 
            canvas.renderAll();

            // Extract pixels directly using logical multipliers
            let croppedDataUrl = canvas.toDataURL({ 
                format: 'png', 
                left: lLeft, top: lTop, width: lWidth, height: lHeight, 
                multiplier: 1 / zoom 
            });

            // Rebuild canvas to crop size
            canvas.clear(); 
            LOGICAL_W = Math.round(lWidth); LOGICAL_H = Math.round(lHeight);
            
            document.getElementById('resizeW').value = LOGICAL_W; 
            document.getElementById('resizeH').value = LOGICAL_H;
            
            let currentBg = document.getElementById('bgColorPicker').value;
            canvas.backgroundColor = currentBg === 'transparent' ? null : currentBg;

            fabric.Image.fromURL(croppedDataUrl, function(img) {
                img.set({ left: LOGICAL_W / 2, top: LOGICAL_H / 2, originX: 'center', originY: 'center' });
                canvas.add(img); canvas.setActiveObject(img);
                
                fitCanvasToScreen(); cancelCrop(); showLoading(false);
            });
        }, 200);
    }
    
    function cancelCrop() {
        if(cropZone) { 
            canvas.remove(cropZone); 
            cropZone = null; 
        }
        document.getElementById('btnStartCrop').style.display = 'block'; 
        document.getElementById('cropOptions').style.display = 'none';
    }

    // ==========================================
    // 7. SHADOW & BORDERS (NEW)
    // ==========================================
    function updateObjectShadowBorder() {
        const obj = canvas.getActiveObject(); 
        if(!obj) return;

        // Shadow
        const sBlur = parseInt(document.getElementById('shadowBlur').value); 
        const sOffsetX = parseInt(document.getElementById('shadowOffsetX').value); 
        const sOffsetY = parseInt(document.getElementById('shadowOffsetY').value); 
        const sColor = document.getElementById('shadowColor').value;
        
        document.getElementById('valSBlur').innerText = sBlur;
        document.getElementById('valOX').innerText = sOffsetX;
        document.getElementById('valOY').innerText = sOffsetY;

        if (sBlur > 0 || sOffsetX !== 0 || sOffsetY !== 0) {
            obj.set('shadow', new fabric.Shadow({ color: sColor, blur: sBlur, offsetX: sOffsetX, offsetY: sOffsetY }));
        } else {
            obj.set('shadow', null);
        }

        // Image/Shape Border
        const bWidth = parseInt(document.getElementById('objBorderWidth').value);
        const bColor = document.getElementById('objBorderColor').value;
        
        document.getElementById('valBWidth').innerText = bWidth;

        if(obj.type === 'image' || obj.type === 'rect' || obj.type === 'circle' || obj.type === 'triangle') {
            obj.set({ stroke: bColor, strokeWidth: bWidth });
        }

        canvas.renderAll();
    }

    function syncEffectsPanel() {
        const obj = canvas.getActiveObject();
        if(obj) {
            document.getElementById('objOpacity').value = obj.opacity !== undefined ? obj.opacity : 1;
            document.getElementById('valOpacity').innerText = Math.round((obj.opacity || 1) * 100) + '%';
            
            if(obj.shadow) {
                document.getElementById('shadowBlur').value = obj.shadow.blur || 0; 
                document.getElementById('shadowOffsetX').value = obj.shadow.offsetX || 0; 
                document.getElementById('shadowOffsetY').value = obj.shadow.offsetY || 0; 
                document.getElementById('shadowColor').value = obj.shadow.color || '#000000';
            } else {
                document.getElementById('shadowBlur').value = 0; document.getElementById('shadowOffsetX').value = 0; document.getElementById('shadowOffsetY').value = 0;
            }

            if(obj.strokeWidth !== undefined) {
                document.getElementById('objBorderWidth').value = obj.strokeWidth;
                document.getElementById('objBorderColor').value = obj.stroke || '#ffffff';
                document.getElementById('valBWidth').innerText = obj.strokeWidth;
            }
        }
    }

    // ==========================================
    // 8. FILTERS & ADJUSTMENTS
    // ==========================================
    function updateOpacity() {
        const obj = canvas.getActiveObject(); if(!obj) return;
        const opacity = parseFloat(document.getElementById('objOpacity').value);
        obj.set('opacity', opacity); document.getElementById('valOpacity').innerText = Math.round(opacity * 100) + '%';
        canvas.renderAll();
    }

    function applyFilters() {
        const obj = canvas.getActiveObject(); 
        if(!obj || obj.type !== 'image') return;
        
        const b = parseFloat(document.getElementById('filterBrightness').value); 
        const c = parseFloat(document.getElementById('filterContrast').value); 
        const s = parseFloat(document.getElementById('filterSaturation').value);
        const n = parseInt(document.getElementById('filterNoise').value);
        const p = parseInt(document.getElementById('filterPixelate').value);
        const blur = parseFloat(document.getElementById('filterBlur').value);
        
        document.getElementById('valB').innerText = b; 
        document.getElementById('valC').innerText = c; 
        document.getElementById('valS').innerText = s; 
        document.getElementById('valN').innerText = n; 
        document.getElementById('valP').innerText = p; 
        document.getElementById('valBlur').innerText = blur; 

        // Preserve AI filters, replace basics
        obj.filters = obj.filters.filter(f => 
            !(f instanceof fabric.Image.filters.Brightness) && 
            !(f instanceof fabric.Image.filters.Contrast) && 
            !(f instanceof fabric.Image.filters.Saturation) &&
            !(f instanceof fabric.Image.filters.Noise) &&
            !(f instanceof fabric.Image.filters.Pixelate) &&
            !(f instanceof fabric.Image.filters.Blur)
        );
        
        if (b !== 0) obj.filters.push(new fabric.Image.filters.Brightness({ brightness: b }));
        if (c !== 0) obj.filters.push(new fabric.Image.filters.Contrast({ contrast: c }));
        if (s !== 0) obj.filters.push(new fabric.Image.filters.Saturation({ saturation: s }));
        if (n !== 0) obj.filters.push(new fabric.Image.filters.Noise({ noise: n }));
        if (p !== 1) obj.filters.push(new fabric.Image.filters.Pixelate({ blocksize: p }));
        if (blur !== 0) obj.filters.push(new fabric.Image.filters.Blur({ blur: blur }));
        
        obj.applyFilters(); canvas.renderAll();
    }

    function applySpecialFilter(type) {
        const obj = canvas.getActiveObject(); if(!obj || obj.type !== 'image') return;
        let filter; 
        if(type === 'grayscale') filter = new fabric.Image.filters.Grayscale(); 
        if(type === 'sepia') filter = new fabric.Image.filters.Sepia(); 
        if(type === 'invert') filter = new fabric.Image.filters.Invert(); 
        if(type === 'vintage') filter = new fabric.Image.filters.Vintage();
        
        if(filter) { obj.filters.push(filter); obj.applyFilters(); canvas.renderAll(); }
    }

    function resetImageFilters() {
        const obj = canvas.getActiveObject(); 
        if(!obj || obj.type !== 'image') return;
        
        document.getElementById('filterBrightness').value = 0; document.getElementById('filterContrast').value = 0; document.getElementById('filterSaturation').value = 0; 
        document.getElementById('filterNoise').value = 0; document.getElementById('filterPixelate').value = 1; document.getElementById('filterBlur').value = 0; 
        
        document.getElementById('valB').innerText = 0; document.getElementById('valC').innerText = 0; document.getElementById('valS').innerText = 0; 
        document.getElementById('valN').innerText = 0; document.getElementById('valP').innerText = 1; document.getElementById('valBlur').innerText = 0; 
        
        obj.filters = []; obj.applyFilters(); canvas.renderAll();
    }

    // ==========================================
    // 9. AI PRO (ENHANCE, COLORIZE, OBJECT REMOVER)
    // ==========================================
    function applyAIEnhance() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') { alert("Select photo first."); return; }
        
        showLoading(true, "AI Enhancing Image (HD)...");
        setTimeout(() => {
            if(!obj.originalFilters) obj.originalFilters = [...obj.filters];
            
            let sharpenMatrix = [  0, -1,  0, -1,  5, -1, 0, -1,  0 ];
            let filter = new fabric.Image.filters.Convolute({ matrix: sharpenMatrix });
            let contrast = new fabric.Image.filters.Contrast({ contrast: 0.15 });
            
            obj.filters.push(filter, contrast); obj.applyFilters(); canvas.renderAll(); showLoading(false);
        }, 500);
    }

    function applyAIColorize() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') { alert("Select photo first."); return; }
        
        showLoading(true, "AI Colorizing...");
        setTimeout(() => {
            if(!obj.originalFilters) obj.originalFilters = [...obj.filters];
            let saturation = new fabric.Image.filters.Saturation({ saturation: 0.85 });
            let brightness = new fabric.Image.filters.Brightness({ brightness: 0.05 });
            
            obj.filters.push(saturation, brightness); obj.applyFilters(); canvas.renderAll(); showLoading(false);
        }, 500);
    }

    function resetAIFilters() {
        const obj = canvas.getActiveObject();
        if(!obj || obj.type !== 'image') return;
        if(obj.originalFilters) {
            obj.filters = [...obj.originalFilters]; obj.applyFilters(); canvas.renderAll();
        }
    }

    // --- ADVANCED INPAINTING (OBJECT REMOVER - FIXED WHITE BOX) ---
    function toggleEraserMask() {
        isMasking = !isMasking;
        const btn = document.getElementById('btnEraserMask');
        const btnApply = document.getElementById('btnApplyErase');
        
        if (isMasking) {
            btn.innerHTML = '<i class="fas fa-times"></i> Cancel Masking';
            btn.style.background = '#ef4444';
            btnApply.style.display = 'flex'; 
            
            canvas.isDrawingMode = true;
            canvas.freeDrawingBrush.color = 'rgba(239, 68, 68, 0.7)'; // Mask Color
            updateEraserSize();
        } else {
            btn.innerHTML = '<i class="fas fa-paint-brush"></i> 1. Start Masking';
            btn.style.background = '#dc2626';
            btnApply.style.display = 'none';
            canvas.isDrawingMode = false;
            
            // Remove drawn masks if cancelled
            maskPathHistory.forEach(path => canvas.remove(path));
            maskPathHistory = [];
            canvas.renderAll();
        }
    }
    
    function updateEraserSize() {
        if(canvas.isDrawingMode && isMasking) {
            canvas.freeDrawingBrush.width = parseInt(document.getElementById('eraserSize').value) || 20;
        }
    }

    function applyObjectRemoval() {
        if(maskPathHistory.length === 0) { alert("Move the red brush over the object to be removed first."); return; }
        
        showLoading(true, "AI Inpainting (Removing Object)...");
        
        setTimeout(() => {
            // Group the masks to get bounding box
            let group = new fabric.Group(maskPathHistory);
            let rect = group.getBoundingRect();
            
            // Hide masks temporarily so they aren't cloned
            maskPathHistory.forEach(path => path.visible = false);
            canvas.renderAll();

            // Extract the background area slightly LARGER than the mask (for cloning surrounding pixels)
            let cropDataUrl = canvas.toDataURL({ 
                format: 'jpeg', left: rect.left - 15, top: rect.top - 15, 
                width: rect.width + 30, height: rect.height + 30, multiplier: 1 
            });

            fabric.Image.fromURL(cropDataUrl, function(img) {
                // Apply a heavy blur to the cloned patch to create a seamless blend (Simulated Inpainting)
                let blurFilter = new fabric.Image.filters.Blur({ blur: 0.6 });
                img.filters.push(blurFilter);
                img.applyFilters();
                
                // Use the red masks as a clip path to only show the blurred replacement WHERE the user painted
                let clipGroup = new fabric.Group(maskPathHistory.map(p => {
                    let clone = fabric.util.object.clone(p);
                    // Adjust coordinates relative to the new patch image
                    clone.set({ left: clone.left - rect.left + 15, top: clone.top - rect.top + 15, visible: true, fill: 'black', stroke: 'black' });
                    return clone;
                }));

                img.set({
                    left: rect.left - 15, top: rect.top - 15,
                    selectable: true, evented: true,
                    clipPath: clipGroup // MAGIC: Only shows blurred pixels inside the brushed area!
                });

                // Delete original red masks
                maskPathHistory.forEach(path => canvas.remove(path));
                maskPathHistory = [];
                
                canvas.add(img); canvas.renderAll();
                toggleEraserMask(); // Turn off mask mode
                showLoading(false);
            });
        }, 500);
    }

    // ==========================================
    // 10. AI REMOVE BG API
    // ==========================================
    async function removeBackgroundAI() {
        const activeObj = canvas.getActiveObject();
        if(!activeObj || activeObj.type !== 'image') { alert("Please select a photo first."); return; }
        
        const apiKey = document.getElementById('removeBgApiKey').value;
        if(!apiKey || apiKey.trim() === '') { alert("API Key is missing."); return; }

        showLoading(true, "AI body scanning removing background...");

        try {
            const imgDataUrl = activeObj.toDataURL({ format: 'png', multiplier: 1 }); 
            const base64Image = imgDataUrl.split(',')[1];
            
            const response = await fetch('https://api.remove.bg/v1.0/removebg', { 
                method: 'POST', headers: { 'X-Api-Key': apiKey, 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ image_file_b64: base64Image, size: 'auto' }) 
            });
            
            if (!response.ok) throw new Error("API Error");
            
            const blob = await response.blob(); 
            const transparentImageUrl = URL.createObjectURL(blob);
            
            fabric.Image.fromURL(transparentImageUrl, function(newImg) {
                newImg.set({ 
                    left: activeObj.left, top: activeObj.top, scaleX: activeObj.scaleX, scaleY: activeObj.scaleY, 
                    angle: activeObj.angle, originX: activeObj.originX, originY: activeObj.originY, 
                    cornerColor: '#38bdf8', borderColor: '#38bdf8', transparentCorners: false 
                });
                
                canvas.remove(activeObj); canvas.add(newImg); canvas.setActiveObject(newImg); 
                showLoading(false);
            });
        } catch (error) { alert("❌ Failed to remove background. API key limit may have been exceeded."); showLoading(false); }
    }

    // ==========================================
    // 11. TYPOGRAPHY ENGINE
    // ==========================================
    function addText() {
        const color = document.getElementById('textColorPicker').value;
        const font = document.getElementById('fontFamily').value.replace(/['"]/g, '');
        const size = parseInt(document.getElementById('fontSizeSlider').value) || 50;
        
        const text = new fabric.IText('Type here / Text', {
            left: LOGICAL_W / 2, top: LOGICAL_H / 2, originX: 'center', originY: 'center',
            fontFamily: font, fill: color, fontSize: size, fontWeight: 'bold',
            borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false, padding: 10,
            stroke: document.getElementById('textStrokeColor').value, strokeWidth: parseFloat(document.getElementById('textStrokeWidth').value)
        });
        
        canvas.add(text); canvas.setActiveObject(text); text.enterEditing(); text.selectAll(); syncTextPanel();
    }

    function updateTextProps() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            let newSize = parseInt(document.getElementById('fontSizeSlider').value);
            obj.set({
                fill: document.getElementById('textColorPicker').value,
                backgroundColor: document.getElementById('textBgColorPicker').value,
                fontFamily: document.getElementById('fontFamily').value.replace(/['"]/g, ''),
                fontSize: newSize,
                stroke: document.getElementById('textStrokeColor').value,
                strokeWidth: parseFloat(document.getElementById('textStrokeWidth').value),
                scaleX: 1, scaleY: 1
            });
            document.getElementById('valFontSize').innerText = newSize; canvas.renderAll();
        }
    }

    function clearTextBg() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') { obj.set('backgroundColor', ''); canvas.renderAll(); }
    }

    function syncTextPanel() {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            let currentFont = obj.fontFamily;
            if(currentFont.includes('Gujarati')) currentFont = "'Noto Sans Gujarati', sans-serif";
            else if(currentFont.includes('Hind')) currentFont = "'Hind Vadodara', sans-serif";
            else if(currentFont.includes('Mukta')) currentFont = "'Mukta Vaani', sans-serif";
            else if(currentFont.includes('Rasa')) currentFont = "'Rasa', serif";
            
            try { document.getElementById('fontFamily').value = currentFont; } catch(e){}
            
            let safeSize = Math.round(obj.fontSize * obj.scaleX);
            document.getElementById('fontSizeSlider').value = safeSize; document.getElementById('valFontSize').innerText = safeSize;
            
            document.getElementById('textColorPicker').value = obj.fill || '#000000'; 
            document.getElementById('textBgColorPicker').value = obj.backgroundColor || '#ffffff';
            document.getElementById('textStrokeColor').value = obj.stroke || '#ffffff'; 
            document.getElementById('textStrokeWidth').value = obj.strokeWidth || 0;

            document.getElementById('btnBold').classList.toggle('active', obj.fontWeight === 'bold'); 
            document.getElementById('btnItalic').classList.toggle('active', obj.fontStyle === 'italic'); 
            document.getElementById('btnUnderline').classList.toggle('active', obj.underline); 
            document.getElementById('btnLinethrough').classList.toggle('active', obj.linethrough);

            document.getElementById('btnAlignLeft').classList.toggle('active', obj.textAlign === 'left'); 
            document.getElementById('btnAlignCenter').classList.toggle('active', obj.textAlign === 'center'); 
            document.getElementById('btnAlignRight').classList.toggle('active', obj.textAlign === 'right');
        }
    }

    function toggleTextFormat(format) {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') {
            if(format === 'bold') { obj.set('fontWeight', obj.fontWeight === 'bold' ? 'normal' : 'bold'); }
            if(format === 'italic') { obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic'); }
            if(format === 'underline') { obj.set('underline', !obj.underline); }
            if(format === 'linethrough') { obj.set('linethrough', !obj.linethrough); }
            canvas.renderAll(); syncTextPanel();
        }
    }

    function setTextAlign(alignment) {
        const obj = canvas.getActiveObject();
        if(obj && obj.type === 'i-text') { obj.set('textAlign', alignment); canvas.renderAll(); syncTextPanel(); }
    }

    // ==========================================
    // 12. DRAWING & SHAPES
    // ==========================================
    function toggleDrawMode() {
        canvas.isDrawingMode = !canvas.isDrawingMode; const btn = document.getElementById('btnDrawMode');
        if (canvas.isDrawingMode) { btn.innerHTML = '<i class="fas fa-times"></i> Stop Drawing'; btn.style.background = '#ef4444'; updateBrush(); } 
        else { btn.innerHTML = '<i class="fas fa-pen"></i> Start Drawing Mode'; btn.style.background = '#10b981'; }
    }
    
    function updateBrush() { if (canvas.isDrawingMode && !isMasking) { canvas.freeDrawingBrush.color = document.getElementById('brushColorPicker').value; canvas.freeDrawingBrush.width = parseInt(document.getElementById('brushSize').value) || 5; } }

    function addShape(type) {
        const color = document.getElementById('shapeColorPicker').value;
        const props = { left: LOGICAL_W/2, top: LOGICAL_H/2, fill: color, originX: 'center', originY: 'center', borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false };
        let shape;
        if(type === 'rect') shape = new fabric.Rect({ ...props, width: 150, height: 150 });
        if(type === 'circle') shape = new fabric.Circle({ ...props, radius: 75 });
        if(type === 'triangle') shape = new fabric.Triangle({ ...props, width: 150, height: 150 });
        if(type === 'star') { 
            const points = [{x: 75, y: 0}, {x: 90, y: 52}, {x: 150, y: 52}, {x: 102, y: 85}, {x: 120, y: 142}, {x: 75, y: 109}, {x: 30, y: 142}, {x: 48, y: 85}, {x: 0, y: 52}, {x: 60, y: 52}]; 
            shape = new fabric.Polygon(points, { ...props }); 
        }
        if(type === 'line') { shape = new fabric.Line([0, 0, 300, 0], { left: LOGICAL_W/2, top: LOGICAL_H/2, stroke: color, strokeWidth: 10, originX: 'center', originY: 'center', borderColor: '#38bdf8', cornerColor: '#38bdf8', transparentCorners: false }); }
        
        if(shape) { canvas.add(shape); canvas.setActiveObject(shape); }
    }

    // ==========================================
    // 13. EXPORT HD IMAGE
    // ==========================================
    async function handleExport() {
        if(cropZone) cancelCrop();
        if(isMasking) toggleEraserMask();
        
        // Hide Grid Lines
        canvas.getObjects().forEach(obj => { if(obj.isGrid) obj.visible = false; });
        canvas.discardActiveObject(); canvas.renderAll();

        if (false && userRole !== 'admin') {
            let confirmMsg = `${currency}${cardCost} will be deducted from wallet to download HD photo.\nDo you want to proceed?`;
            if (!confirm(confirmMsg)) {
                canvas.getObjects().forEach(obj => { if(obj.isGrid) obj.visible = true; }); canvas.renderAll(); return; 
            }
            try {
                let formData = new FormData(); formData.append('service_type', 'Pro Photo Studio Export');
                let response = await fetch(baseUrl + 'app/deduct_poster_balance.php', { method: 'POST', body: formData });
                let text = await response.text(); let result = JSON.parse(text);
                if (!result.success) { alert("❌ Error: " + result.message); return; }
            } catch (error) { alert("❌ Network error."); return; }
        }

        showLoading(true, "Saving High Quality Masterpiece...");

        setTimeout(() => {
            const currentZoom = canvas.getZoom();
            canvas.setZoom(1); canvas.setWidth(LOGICAL_W); canvas.setHeight(LOGICAL_H);

            const dataUrl = canvas.toDataURL({ format: canvas.backgroundColor ? 'jpeg' : 'png', quality: 1.0, multiplier: 2 });
            
            canvas.setZoom(currentZoom); fitCanvasToScreen();
            canvas.getObjects().forEach(obj => { if(obj.isGrid) obj.visible = true; }); canvas.renderAll();

            const link = document.createElement('a'); link.download = `Studio_Masterpiece_${Date.now()}.${canvas.backgroundColor ? 'jpg' : 'png'}`; link.href = dataUrl; link.click();
            showLoading(false);
        }, 800);
    }
