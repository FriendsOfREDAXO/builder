(function () {
    "use strict";

    // Helper: Generate UUIDs
    function generateId() {
        return 'bld_' + Math.random().toString(36).substr(2, 9);
    }

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Initialize all workspace instances on load
    document.addEventListener("DOMContentLoaded", function () {
        const wrappers = document.querySelectorAll(".builder-editor-wrapper");
        wrappers.forEach(function (wrapper) {
            initWorkspace(wrapper);
        });
    });

    function initWorkspace(wrapper) {
        const fieldId = wrapper.dataset.fieldId;
        const textarea = document.getElementById(fieldId);
        const container = wrapper.querySelector(".builder-editor-container");
        const canvas = wrapper.querySelector(".builder-canvas");
        const emptyState = wrapper.querySelector(".canvas-empty-state");
        const modal = wrapper.querySelector(".builder-modal");
        if (modal) {
            document.body.appendChild(modal);
        }
        const modalForm = modal.querySelector(".builder-modal-form");
        const modalBody = modal.querySelector(".builder-modal-body");
        const btnSave = modal.querySelector(".btn-save");
        const btnCancel = modal.querySelector(".btn-cancel");
        const btnClose = modal.querySelector(".builder-modal-close");

        // Parse elements configurations
        const elementsSchema = JSON.parse(container.dataset.elements || "{}");

        // Load current state
        let canvasData = [];
        try {
            canvasData = JSON.parse(textarea.value || "[]");
        } catch (e) {
            canvasData = [];
        }

        // Active node during edit modal
        let currentlyEditingElement = null;

        // Active drag source
        let dragSource = null;

        function saveState() {
            textarea.value = JSON.stringify(canvasData);
            // Trigger change event for YForm/MBlock monitoring
            const event = new Event('change', { bubbles: true });
            textarea.dispatchEvent(event);
        }

        function renderCanvas() {
            canvas.innerHTML = '';
            
            if (canvasData.length === 0) {
                emptyState.style.display = 'block';
                return;
            }
            emptyState.style.display = 'none';

            canvasData.forEach(function (row, rowIdx) {
                canvas.appendChild(renderRowDOM(row, rowIdx));
            });
        }

        // Render Visual Row DOM
        function renderRowDOM(row, rowIdx) {
            const rowEl = document.createElement("div");
            rowEl.className = "node-grid-row";
            rowEl.dataset.rowId = row.id;
            rowEl.draggable = true;

            // Row Header
            const header = document.createElement("div");
            header.className = "row-header";
            
            let colCount = row.columns.length;
            header.innerHTML = `
                <div class="row-title"><i class="rex-icon fa-bars drag-handle"></i> Layout-Zeile (${colCount} Spalten - ${row.layout})</div>
                <div class="row-actions">
                    <button type="button" class="row-btn btn-delete" title="Zeile löschen"><i class="rex-icon fa-trash"></i></button>
                </div>
            `;

            header.querySelector(".btn-delete").addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm("Ganze Layout-Zeile und alle darin enthaltenen Elemente löschen?")) {
                    canvasData.splice(rowIdx, 1);
                    saveState();
                    renderCanvas();
                }
            });

            // Drag reorder row
            rowEl.addEventListener("dragstart", function (e) {
                e.stopPropagation();
                e.dataTransfer.setData("text/plain", "row");
                dragSource = { type: 'row', index: rowIdx };
                rowEl.style.opacity = '0.4';
            });
            rowEl.addEventListener("dragend", function (e) {
                e.stopPropagation();
                rowEl.style.opacity = '1';
                dragSource = null;
            });

            // Grid wrapper
            const gridWrapper = document.createElement("div");
            gridWrapper.className = "grid-cols-wrapper";
            gridWrapper.style.gridTemplateColumns = row.layout || "1fr";

            row.columns.forEach(function (col, colIdx) {
                const colEl = document.createElement("div");
                colEl.className = "grid-column-canvas";
                colEl.dataset.colId = col.id;

                // Elements inside column
                if (col.elements && col.elements.length > 0) {
                    col.elements.forEach(function (elNode, elIdx) {
                        colEl.appendChild(renderElementDOM(elNode, rowIdx, colIdx, elIdx));
                    });
                }

                // Drop Handling for Column
                setupDropZone(colEl, rowIdx, colIdx);

                gridWrapper.appendChild(colEl);
            });

            rowEl.appendChild(header);
            rowEl.appendChild(gridWrapper);

            return rowEl;
        }

        // Render Visual Widget DOM
        function renderElementDOM(elNode, rowIdx, colIdx, elIdx) {
            const schema = elementsSchema[elNode.type] || { label: elNode.type, icon: 'fa-cube' };
            const elDom = document.createElement("div");
            elDom.className = "node-element";
            elDom.dataset.elId = elNode.id;
            elDom.draggable = true;
            // Generate Preview HTML based on element type
            let previewHtml = '';
            const values = elNode.values || {};

            if (elNode.type === 'headline') {
                const text = values.text || '';
                const tag = values.tag || 'h2';
                previewHtml = text 
                    ? `<${tag} style="margin:0; font-weight:600; color:#1e293b;">${escapeHtml(text)}</${tag}>`
                    : `<span style="color:#94a3b8; font-style:italic;">Überschrift eingeben...</span>`;
            } else if (elNode.type === 'text') {
                const content = values.content || '';
                previewHtml = content
                    ? `<div style="max-height:120px; overflow:hidden; text-overflow:ellipsis; font-size:13px; line-height:1.5; color:#475569;">${content}</div>`
                    : `<span style="color:#94a3b8; font-style:italic;">Text eingeben...</span>`;
            } else if (elNode.type === 'image') {
                const media = values.media || '';
                const alt = values.alt || '';
                const align = values.align || 'center';
                const alignStyle = align === 'right' ? 'right' : (align === 'left' ? 'left' : 'center');
                
                if (media) {
                    previewHtml = `
                        <div style="text-align:${alignStyle};">
                            <img src="/media/${media}" alt="${escapeHtml(alt)}" style="max-width:100%; max-height:150px; border-radius:4px; display:inline-block; border:1px solid #e2e8f0; padding:2px;">
                            <div style="font-size:10px; color:#64748b; margin-top:4px;">Datei: ${escapeHtml(media)}</div>
                        </div>
                    `;
                } else {
                    previewHtml = `
                        <div style="aspect-ratio:16/9; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:4px; display:flex; flex-direction:column; justify-content:center; align-items:center; color:#94a3b8; padding:15px 0;">
                            <i class="rex-icon fa-picture-o" style="font-size:24px; margin-bottom:5px; color:#cbd5e1;"></i>
                            <span style="font-size:11px;">Bild auswählen (16:9 Platzhalter)</span>
                        </div>
                    `;
                }
            } else if (elNode.type === 'button') {
                const label = values.label || 'Button';
                const style = values.style || 'btn-primary';
                
                let btnStyle = 'background-color:#2b3e50; color:#fff; border:none;';
                if (style === 'btn-default') {
                    btnStyle = 'background-color:#fff; color:#333; border:1px solid #ccc;';
                } else if (style === 'btn-link') {
                    btnStyle = 'background:none; color:#2b3e50; border:none; text-decoration:underline; box-shadow:none;';
                }
                
                previewHtml = `
                    <div style="text-align:left;">
                        <button type="button" class="btn" style="${btnStyle} pointer-events:none; font-size:12px; padding:4px 12px; border-radius:4px;">${escapeHtml(label)}</button>
                    </div>
                `;
            } else {
                previewHtml = `<span style="color:#64748b; font-style:italic;">Keine Live-Vorschau verfügbar.</span>`;
            }

            elDom.innerHTML = `
                <div class="element-header">
                    <div class="element-info">
                        <i class="rex-icon ${schema.icon}"></i>
                        <strong>${escapeHtml(schema.label)}</strong>
                        <span>(${escapeHtml(elNode.type)})</span>
                    </div>
                    <div class="element-actions">
                        <button type="button" class="element-btn btn-edit" title="Konfigurieren"><i class="rex-icon fa-cog"></i></button>
                        <button type="button" class="element-btn btn-delete" title="Löschen"><i class="rex-icon fa-trash"></i></button>
                    </div>
                </div>
                <div class="element-preview">
                    ${previewHtml}
                </div>
            `;

            elDom.querySelector(".btn-edit").addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                openSettingsModal(elNode);
            });

            elDom.querySelector(".btn-delete").addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm("Element löschen?")) {
                    canvasData[rowIdx].columns[colIdx].elements.splice(elIdx, 1);
                    saveState();
                    renderCanvas();
                }
            });

            // Drag element
            elDom.addEventListener("dragstart", function (e) {
                e.stopPropagation();
                e.dataTransfer.setData("text/plain", "element");
                dragSource = { type: 'element', rowIdx: rowIdx, colIdx: colIdx, elIdx: elIdx };
                elDom.style.opacity = '0.4';
            });
            elDom.addEventListener("dragend", function (e) {
                e.stopPropagation();
                elDom.style.opacity = '1';
                dragSource = null;
            });

            return elDom;
        }

        // Setup drop zones for column canvases
        function setupDropZone(dropZoneEl, targetRowIdx, targetColIdx) {
            dropZoneEl.addEventListener("dragover", function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZoneEl.classList.add("drag-over");
            });

            dropZoneEl.addEventListener("dragleave", function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZoneEl.classList.remove("drag-over");
            });

            dropZoneEl.addEventListener("drop", function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZoneEl.classList.remove("drag-over");

                if (dragSource) {
                    if (dragSource.type === 'element') {
                        // Move element
                        const elNode = canvasData[dragSource.rowIdx].columns[dragSource.colIdx].elements.splice(dragSource.elIdx, 1)[0];
                        canvasData[targetRowIdx].columns[targetColIdx].elements.push(elNode);
                        saveState();
                        renderCanvas();
                    } else if (dragSource.type === 'new_element') {
                        // Drop new element from sidebar
                        const schema = elementsSchema[dragSource.elementType];
                        const defaultValues = {};
                        if (schema && schema.fields) {
                            schema.fields.forEach(function (f) {
                                defaultValues[f.name] = f.default !== undefined ? f.default : '';
                            });
                        }

                        const newEl = {
                            id: generateId(),
                            type: dragSource.elementType,
                            values: defaultValues
                        };

                        canvasData[targetRowIdx].columns[targetColIdx].elements.push(newEl);
                        saveState();
                        renderCanvas();
                    }
                }
            });
        }

        // Handle Row canvas dragging and drop
        canvas.addEventListener("dragover", function (e) {
            e.preventDefault();
        });

        canvas.addEventListener("drop", function (e) {
            e.preventDefault();
            if (dragSource && dragSource.type === 'row') {
                // Determine drop index based on layout children
                const rows = Array.from(canvas.querySelectorAll(".node-grid-row"));
                const dropY = e.clientY;
                let targetIdx = canvasData.length;

                for (let i = 0; i < rows.length; i++) {
                    const box = rows[i].getBoundingClientRect();
                    const middle = box.top + box.height / 2;
                    if (dropY < middle) {
                        targetIdx = i;
                        break;
                    }
                }

                // Move row
                const row = canvasData.splice(dragSource.index, 1)[0];
                if (targetIdx > dragSource.index) {
                    targetIdx--;
                }
                canvasData.splice(targetIdx, 0, row);
                saveState();
                renderCanvas();
            }
        });

        // Sidebar items drag handlers
        const sidebarItems = wrapper.querySelectorAll(".sidebar-item");
        sidebarItems.forEach(function (item) {
            item.addEventListener("dragstart", function (e) {
                e.dataTransfer.setData("text/plain", "sidebar");
                const layout = item.dataset.layout;
                const type = item.dataset.type;
                if (layout) {
                    dragSource = { type: 'new_row', layout: layout };
                } else if (type) {
                    dragSource = { type: 'new_element', elementType: type };
                }
            });

            item.addEventListener("dragend", function () {
                dragSource = null;
            });
        });

        // Canvas drop area helper for new items
        canvas.addEventListener("dragover", function (e) {
            e.preventDefault();
        });
        
        canvas.addEventListener("drop", function (e) {
            if (dragSource) {
                if (dragSource.type === 'new_row') {
                    // Create new layout row
                    const cols = dragSource.layout.split(" ");
                    const columnsArr = [];
                    cols.forEach(function () {
                        columnsArr.push({ id: generateId(), elements: [] });
                    });

                    const newRow = {
                        id: generateId(),
                        layout: dragSource.layout,
                        columns: columnsArr
                    };

                    canvasData.push(newRow);
                    saveState();
                    renderCanvas();
                }
            }
        });



        // Open element settings modal
        function openSettingsModal(elNode) {
            currentlyEditingElement = elNode;
            const schema = elementsSchema[elNode.type] || { label: elNode.type, fields: [] };
            
            modal.querySelector(".builder-modal-title").innerText = `${schema.label} konfigurieren`;
            modalBody.innerHTML = '';

            // Generate field inputs dynamically based on config schema
            schema.fields.forEach(function (field) {
                const value = elNode.values[field.name] !== undefined ? elNode.values[field.name] : (field.default || '');
                const formGroup = document.createElement("div");
                formGroup.className = "form-group";

                let inputHtml = '';
                const fieldId = 'fld_' + elNode.id + '_' + field.name;

                if (field.type === 'text') {
                    inputHtml = `<input type="text" class="form-control" id="${fieldId}" value="${escapeHtml(value)}">`;
                } else if (field.type === 'textarea') {
                    inputHtml = `<textarea class="form-control" rows="4" id="${fieldId}">${escapeHtml(value)}</textarea>`;
                } else if (field.type === 'wysiwyg') {
                    inputHtml = `<textarea class="form-control tiny-editor" data-profile="default" id="${fieldId}" rows="6">${escapeHtml(value)}</textarea>`;
                } else if (field.type === 'select') {
                    inputHtml = `<select class="form-control" id="${fieldId}">`;
                    for (const choiceKey in field.choices) {
                        const isSelected = choiceKey === value ? 'selected' : '';
                        inputHtml += `<option value="${choiceKey}" ${isSelected}>${escapeHtml(field.choices[choiceKey])}</option>`;
                    }
                    inputHtml += `</select>`;
                } else if (field.type === 'media') {
                    inputHtml = `
                        <div class="builder-widget-wrapper rex-js-widget rex-js-widget-media">
                            <div class="input-group">
                                <input class="form-control" type="text" id="${fieldId}" readonly value="${escapeHtml(value)}">
                                <span class="input-group-btn">
                                    <a href="#" class="btn btn-popup" onclick="openMediaPool('${fieldId}'); return false;" title="Medium auswählen">
                                        <i class="rex-icon rex-icon-open-mediapool"></i>
                                    </a>
                                    <a href="#" class="btn btn-popup" onclick="document.getElementById('${fieldId}').value = ''; return false;" title="Medium löschen">
                                        <i class="rex-icon rex-icon-clear-mediapool"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                    `;
                } else if (field.type === 'link') {
                    inputHtml = `
                        <div class="builder-widget-wrapper rex-js-widget rex-js-widget-link">
                            <div class="input-group">
                                <input class="form-control" type="text" id="${fieldId}_NAME" readonly value="${value ? 'Link ID: ' + value : ''}">
                                <input type="hidden" id="${fieldId}" value="${escapeHtml(value)}">
                                <span class="input-group-btn">
                                    <a href="#" class="btn btn-popup" onclick="openLinkMap('${fieldId}', '&amp;clang=1'); return false;" title="Link auswählen">
                                        <i class="rex-icon rex-icon-open-linkmap"></i>
                                    </a>
                                    <a href="#" class="btn btn-popup" onclick="document.getElementById('${fieldId}').value = ''; document.getElementById('${fieldId}_NAME').value = ''; return false;" title="Link löschen">
                                        <i class="rex-icon rex-icon-clear-linkmap"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                    `;
                    
                    // Bind custom link name observer to update display name automatically when setting ID
                    setTimeout(function() {
                        const hid = document.getElementById(fieldId);
                        const nInput = document.getElementById(fieldId + '_NAME');
                        if (hid && nInput) {
                            setInterval(function() {
                                if (hid.value && nInput.value === '') {
                                    nInput.value = 'Link ID: ' + hid.value;
                                }
                            }, 500);
                        }
                    }, 200);
                }

                formGroup.innerHTML = `
                    <label for="${fieldId}">${escapeHtml(field.label)}</label>
                    ${inputHtml}
                    ${field.notice ? `<p class="help-block">${escapeHtml(field.notice)}</p>` : ''}
                `;

                modalBody.appendChild(formGroup);
            });

            modal.style.display = 'flex';

            // Initialize TinyMCE editors inside the modal
            if (typeof tiny_init === 'function') {
                tiny_init(jQuery(modalBody));
            }
        }

        function closeModal() {
            // Save TinyMCE editor contents back to their textareas
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
                
                // Destroy editor instances inside this modal to prevent orphans
                if (tinymce.editors && typeof tinymce.editors.forEach === 'function') {
                    tinymce.editors.forEach(function (ed) {
                        if (ed && ed.targetElm && typeof ed.targetElm.closest === 'function') {
                            if (document.getElementById(ed.id) && ed.targetElm.closest('.builder-modal')) {
                                tinymce.remove('#' + ed.id);
                            }
                        }
                    });
                }
            }

            modal.style.display = 'none';
            currentlyEditingElement = null;
        }

        // Modal Action buttons
        btnCancel.addEventListener("click", closeModal);
        btnClose.addEventListener("click", closeModal);

        // Prevent enter key from submitting the outer form when typing in modal inputs
        modal.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.keyCode === 13) {
                // Ignore enter key in textareas (they need newline)
                if (e.target.tagName.toLowerCase() !== "textarea") {
                    e.preventDefault();
                    btnSave.click();
                }
            }
        });

        btnSave.addEventListener("click", function () {
            if (!currentlyEditingElement) return;

            const schema = elementsSchema[currentlyEditingElement.type] || { fields: [] };
            
            // Save TinyMCE content first
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            schema.fields.forEach(function (field) {
                const fieldId = 'fld_' + currentlyEditingElement.id + '_' + field.name;
                const input = document.getElementById(fieldId);
                if (input) {
                    currentlyEditingElement.values[field.name] = input.value;
                }
            });

            saveState();
            closeModal();
            renderCanvas();
        });

        // Initialize display
        renderCanvas();
    }

})();
