(function() {
    // Wait for WordPress dependencies to be available
    function initProductBlock() {
        if (typeof wp === 'undefined' || 
            !wp.blocks || 
            !wp.element || 
            !wp.components || 
            !wp.i18n || 
            !wp.data || 
            !wp.data.withSelect) {
            setTimeout(initProductBlock, 100);
            return;
        }
        
        const { registerBlockType } = wp.blocks;
        const { createElement: el, Fragment } = wp.element;
        const { 
            PanelBody, 
            SelectControl, 
            TextControl, 
            Button, 
            Modal, 
            Spinner,
            Placeholder,
            Card,
            CardBody,
            CardMedia,
            CardHeader
        } = wp.components;
        const { __ } = wp.i18n;
        const { withSelect, useDispatch } = wp.data;

    // Product Selector Modal Component
    const ProductSelectorModal = (props) => {
        const { isOpen, onClose, onSelect, products, isLoading, searchTerm, onSearch } = props;

        if (!isOpen) return null;

        return el(Modal, {
            title: __('Select Product'),
            onRequestClose: onClose,
            className: 'apd-product-selector-modal'
        }, [
            el('div', { key: 'search', className: 'apd-product-search' }, [
                el(TextControl, {
                    placeholder: __('Search products...'),
                    value: searchTerm,
                    onChange: onSearch,
                    className: 'apd-search-input'
                })
            ]),
            el('div', { key: 'content', className: 'apd-product-list-container' }, [
                isLoading ? el(Spinner, { key: 'spinner' }) : null,
                products && products.length > 0 ? 
                    el('div', { key: 'products', className: 'apd-product-grid' }, 
                        products.map(product => 
                            el(Card, {
                                key: product.id,
                                className: 'apd-product-card apd-product-card-text-only',
                                onClick: () => onSelect(product)
                            }, [
                                el(CardHeader, {
                                    className: 'apd-product-header'
                                }, [
                                    el('h3', { className: 'apd-product-title' }, product.title),
                                    el('div', { className: 'apd-product-price' }, `$${product.price}`)
                                ])
                            ])
                        )
                    ) :
                    !isLoading ? el(Placeholder, {
                        key: 'no-products',
                        icon: 'products',
                        label: __('No products found')
                    }) : null
            ])
        ]);
    };

    // Template System for Dynamic Rendering
    const TemplateRenderer = {
        // Default template structure
        getDefaultTemplate: () => ({
            logo: {
                type: 'svg',
                content: 'default-freight-logo',
                position: { x: 0, y: 0, width: 400, height: 200 }
            },
            fields: [
                {
                    id: 'company_name',
                    type: 'text',
                    label: 'Company Name',
                    placeholder: 'Enter company name',
                    position: { x: 20, y: 60, fontSize: 48 },
                    defaultValue: 'HOUSTON, TX'
                },
                {
                    id: 'mc_number',
                    type: 'text',
                    label: 'MC Number',
                    placeholder: 'Enter MC number',
                    position: { x: 20, y: 120, fontSize: 36 },
                    defaultValue: 'MC 747394'
                },
                {
                    id: 'usdot_number',
                    type: 'text',
                    label: 'USDOT Number',
                    placeholder: 'Enter USDOT number',
                    position: { x: 20, y: 170, fontSize: 36 },
                    defaultValue: 'USDOT 2149161'
                },
                {
                    id: 'vin_number',
                    type: 'text',
                    label: 'VIN',
                    placeholder: 'Enter VIN number',
                    position: { x: 20, y: 220, fontSize: 36 },
                    defaultValue: 'VIN 88H4264H'
                },
                {
                    id: 'truck_number',
                    type: 'text',
                    label: 'Truck Number',
                    placeholder: 'Enter truck number',
                    position: { x: 20, y: 270, fontSize: 36 },
                    defaultValue: '5561'
                }
            ]
        }),

        // Render template to canvas
        renderTemplate: (template, fieldValues = {}) => {
            
            const elements = [];
            
            // Render logo
            if (template.logo) {
                if (template.logo.type === 'image' && template.logo.content) {
                    // Render product logo image
                    elements.push(
                        el('div', { key: 'logo', className: 'apd-logo-container' }, [
                            el('img', { 
                                className: 'apd-logo-image',
                                src: template.logo.content,
                                alt: 'Product Logo',
                                style: {
                                    maxWidth: template.logo.position.width + 'px',
                                    maxHeight: template.logo.position.height + 'px',
                                    width: 'auto',
                                    height: 'auto'
                                }
                            })
                        ])
                    );
                } else if (template.logo.type === 'svg' && template.logo.content === 'default-freight-logo') {
                    // Render default FREIGHT logo
                    elements.push(
                        el('div', { key: 'logo', className: 'apd-logo-container' }, [
                            el('svg', { 
                                className: 'apd-logo-svg',
                                viewBox: '0 0 1406 682',
                                xmlns: 'http://www.w3.org/2000/svg'
                            }, [
                                el('g', { 
                                    transform: 'translate(0.000000,682.000000) scale(0.100000,-0.100000)',
                                    fill: 'currentColor',
                                    stroke: 'none'
                                }, [
                                    el('path', { d: 'M0 3490 l0 -620 220 0 220 0 0 230 0 230 370 0 371 0 43 48 c24 26 89 99 145 162 l102 115 -516 3 -515 2 0 65 0 65 409 0 409 0 113 128 c62 70 126 142 143 160 l30 32 -772 0 -772 0 0 -620z' }),
                                    el('path', { d: 'M1742 3953 l-140 -158 667 -6 c426 -3 677 -9 697 -16 92 -32 124 -65 124 -129 0 -55 -27 -87 -98 -116 l-57 -23 -672 -3 -673 -3 0 -314 0 -315 225 0 225 0 0 155 0 155 300 0 299 0 213 -155 213 -155 319 0 319 0 -105 78 c-58 42 -171 124 -252 182 -80 58 -145 107 -146 110 0 4 17 12 38 19 101 36 226 141 270 227 47 92 47 225 0 324 -71 149 -281 258 -553 290 -50 5 -310 10 -581 10 l-492 0 -140 -157z' }),
                                    el('path', { d: 'M3790 3490 l0 -620 629 0 629 0 136 154 c75 85 136 157 136 160 0 3 -245 6 -545 6 l-545 0 0 70 0 70 369 0 370 0 39 43 c22 23 87 96 145 162 l106 120 -515 3 -514 2 0 65 0 65 409 0 409 0 141 160 142 160 -771 0 -770 0 0 -620z' }),
                                    el('path', { d: 'M5450 3490 l0 -620 220 0 220 0 0 620 0 620 -220 0 -220 0 0 -620z' }),
                                    el('path', { d: 'M6740 4103 c-297 -31 -556 -178 -655 -374 -14 -26 -32 -86 -41 -133 -62 -313 162 -594 558 -698 80 -21 104 -22 661 -25 l577 -4 0 370 0 371 -642 0 -642 0 69 -72 c39 -40 106 -110 150 -155 l80 -83 272 0 273 0 0 -55 0 -55 -297 0 c-228 0 -313 4 -361 15 -203 48 -309 193 -260 358 25 84 140 176 259 208 28 7 190 13 454 17 l410 5 105 119 c58 66 120 138 138 159 l34 39 -554 -2 c-304 0 -569 -3 -588 -5z' }),
                                    el('path', { d: 'M8010 3490 l0 -620 225 0 225 0 0 230 0 230 420 0 420 0 0 -230 0 -230 220 0 220 0 0 620 0 620 -220 0 -220 0 0 -230 0 -230 -420 0 -420 0 0 230 0 230 -225 0 -225 0 0 -620z' }),
                                    el('path', { d: 'M9968 3952 l-137 -157 287 -5 287 -5 3 -457 2 -458 220 0 220 0 0 460 0 460 213 0 212 0 130 146 c72 81 134 153 138 160 7 12 -109 14 -715 14 l-723 -1 -137 -157z' })
                                ])
                            ])
                        ])
                    );
                }
            }

            // Render text fields from template
            
            
            if (template.fields && Array.isArray(template.fields)) {
                template.fields.forEach((field, index) => {
                    
                    if (field.type === 'text') {
                        const value = fieldValues[field.id] || field.defaultValue || '';
                        elements.push(
                            el('div', { 
                                key: field.id,
                                className: `apd-field apd-field-${field.id}`,
                                style: {
                                    position: 'absolute',
                                    left: (field.position?.x || 20) + 'px',
                                    top: (field.position?.y || 60) + 'px',
                                    fontSize: (field.position?.fontSize || 36) + 'px',
                                    fontWeight: field.fontWeight || 'bold',
                                    fontStyle: field.fontStyle || 'italic',
                                    color: field.color || '#000',
                                    textShadow: field.textShadow || '1px 1px 2px rgba(255,255,255,0.8)',
                                    pointerEvents: 'none',
                                    userSelect: 'none',
                                    textAlign: field.textAlign || 'left'
                                }
                            }, value)
                        );
                    } else if (field.type === 'image') {
                        const imageValue = fieldValues[field.id];
                        if (imageValue) {
                            elements.push(
                                el('div', { 
                                    key: field.id,
                                    className: `apd-field apd-field-${field.id}`,
                                    style: {
                                        position: 'absolute',
                                        left: (field.position?.x || 20) + 'px',
                                        top: (field.position?.y || 60) + 'px',
                                        width: (field.position?.width || 100) + 'px',
                                        height: (field.position?.height || 100) + 'px',
                                        pointerEvents: 'none',
                                        userSelect: 'none'
                                    }
                                }, [
                                    el('img', {
                                        src: typeof imageValue === 'string' ? imageValue : URL.createObjectURL(imageValue),
                                        alt: field.label || 'Field Image',
                                        style: {
                                            width: '100%',
                                            height: '100%',
                                            objectFit: 'contain'
                                        }
                                    })
                                ])
                            );
                        }
                    }
                });
            }

            // Return wrapped in a container div honoring canvas size/background
            
            
            const canvasWidth = template.canvas && template.canvas.width ? template.canvas.width : undefined;
            const canvasHeight = template.canvas && template.canvas.height ? template.canvas.height : undefined;
            const bg = template.canvas && template.canvas.background ? template.canvas.background : null;
            const backgroundStyles = (() => {
                if (!bg) return {};
                // Allow plain CSS string
                if (typeof bg === 'string') {
                    return { background: bg };
                }
                // Solid color
                if ((bg.type === 'color' || bg.type === 'solid') && bg.color) {
                    return { backgroundColor: bg.color };
                }
                // Image background
                if (bg.type === 'image') {
                    const url = bg.url || bg.src || bg.image || bg.imageUrl;
                    if (!url) return {};
                    const size = bg.size || (bg.cover ? 'cover' : (bg.contain ? 'contain' : 'cover'));
                    const position = bg.position || 'center';
                    const repeat = typeof bg.repeat === 'string' ? bg.repeat : (bg.repeat ? 'repeat' : 'no-repeat');
                    return {
                        backgroundImage: `url(${url})`,
                        backgroundSize: size,
                        backgroundPosition: position,
                        backgroundRepeat: repeat
                    };
                }
                // Gradients
                if (bg.type === 'gradient' || bg.type === 'linear-gradient' || bg.type === 'radial-gradient') {
                    const isRadial = bg.type === 'radial-gradient';
                    const angle = typeof bg.angle === 'number' ? `${bg.angle}deg` : (bg.angle || undefined);
                    const direction = bg.direction || undefined; // e.g. 'to bottom'
                    const stops = Array.isArray(bg.stops) ? bg.stops : (Array.isArray(bg.colors) ? bg.colors : []);
                    const stopStr = stops.map((s) => {
                        if (typeof s === 'string') return s;
                        const color = s.color || s[0];
                        const offset = (typeof s.offset === 'number') ? `${Math.round(s.offset * 100)}%` : (s.offset || s[1] || '');
                        return offset ? `${color} ${offset}` : `${color}`;
                    }).join(', ');
                    if (isRadial) {
                        const shape = bg.shape || 'ellipse';
                        const atPos = bg.position ? ` at ${bg.position}` : '';
                        const radialCss = `radial-gradient(${shape}${atPos}, ${stopStr})`;
                        return { backgroundImage: radialCss };
                    } else {
                        const dirOrAngle = direction || angle || '180deg';
                        const linearCss = `linear-gradient(${dirOrAngle}, ${stopStr})`;
                        return { backgroundImage: linearCss };
                    }
                }
                return {};
            })();
            
            return el('div', { 
                className: 'apd-template-container',
                style: { 
                    position: 'relative',
                    width: canvasWidth ? (canvasWidth + 'px') : '100%',
                    height: canvasHeight ? (canvasHeight + 'px') : '100%',
                    minHeight: canvasHeight ? (canvasHeight + 'px') : '400px',
                    ...backgroundStyles
                }
            }, elements);
        },

        // Generate panel inputs from template
        generatePanelInputs: (template, fieldValues = {}, onFieldChange) => {
            if (!template || !Array.isArray(template.fields) || template.fields.length === 0) {
                return [
                    el('div', { key: 'no-fields', className: 'apd-form-group' },
                        __('No customizable fields for this product'))
                ];
            }
            return template.fields.map(field => {
                if (field.type === 'text') {
                    return el('div', { key: field.id, className: 'apd-form-group' }, [
                        el('label', { className: 'apd-form-label', htmlFor: `apd-${field.id}` }, [
                            field.label,
                            field.note ? el('span', { className: 'apd-form-note', style: { marginLeft: '6px', fontSize: '12px', color: '#666', fontStyle: 'italic' } }, ` ${field.note}`) : null
                        ]),
                        el('input', { 
                            type: 'text', 
                            id: `apd-${field.id}`, 
                            className: 'apd-form-input',
                            placeholder: field.placeholder,
                            value: fieldValues[field.id] || field.defaultValue,
                            onChange: (e) => onFieldChange(field.id, e.target.value)
                        })
                    ]);
                } else if (field.type === 'image') {
                    return el('div', { key: field.id, className: 'apd-form-group' }, [
                        el('label', { className: 'apd-form-label', htmlFor: `apd-${field.id}` }, field.label),
                        el('input', { 
                            type: 'file', 
                            id: `apd-${field.id}`, 
                            className: 'apd-form-input',
                            accept: 'image/*',
                            onChange: (e) => onFieldChange(field.id, e.target.files[0])
                        }),
                        fieldValues[field.id] && el('img', {
                            src: URL.createObjectURL(fieldValues[field.id]),
                            alt: field.label,
                            style: { maxWidth: '100px', maxHeight: '100px', marginTop: '10px' }
                        })
                    ]);
                }
                return null;
            }).filter(Boolean);
        }
    };

    // Main Product Block Component
    const ProductBlockEdit = (props) => {
        const { attributes, setAttributes, products, isLoading } = props;
        const { productId, layout, showPrice, showDescription } = attributes;
        const [isModalOpen, setIsModalOpen] = React.useState(false);
        const [searchTerm, setSearchTerm] = React.useState('');
        const [filteredProducts, setFilteredProducts] = React.useState([]);
        const [fieldValues, setFieldValues] = React.useState({});
        const [currentTemplate, setCurrentTemplate] = React.useState(TemplateRenderer.getDefaultTemplate());
        
        const { fetchProducts } = useDispatch('apd/products');

        // Scroll/pan support for large canvases
        const scrollRef = React.useRef(null);
        const [isDragging, setIsDragging] = React.useState(false);
        const dragStateRef = React.useRef({ startX: 0, startY: 0, scrollLeft: 0, scrollTop: 0 });

        const onMouseDownScroll = (e) => {
            if (!scrollRef.current) return;
            setIsDragging(true);
            dragStateRef.current = {
                startX: e.pageX - scrollRef.current.offsetLeft,
                startY: e.pageY - scrollRef.current.offsetTop,
                scrollLeft: scrollRef.current.scrollLeft,
                scrollTop: scrollRef.current.scrollTop
            };
        };
        const onMouseLeaveScroll = () => setIsDragging(false);
        const onMouseUpScroll = () => setIsDragging(false);
        const onMouseMoveScroll = (e) => {
            if (!isDragging || !scrollRef.current) return;
            e.preventDefault();
            const x = e.pageX - scrollRef.current.offsetLeft;
            const y = e.pageY - scrollRef.current.offsetTop;
            const walkX = (x - dragStateRef.current.startX) * -1;
            const walkY = (y - dragStateRef.current.startY) * -1;
            scrollRef.current.scrollLeft = dragStateRef.current.scrollLeft + walkX;
            scrollRef.current.scrollTop = dragStateRef.current.scrollTop + walkY;
        };
        
        const loadProductTemplate = React.useCallback((product) => {
            
            
            // Get product template from product data
            let template = null;
            
            // Try to get template from product meta
            
            
            if (product.template) {
                
                try {
                    template = typeof product.template === 'string' ? JSON.parse(product.template) : product.template;
                } catch (err) {
                    
                    template = null;
                }
            } else if (product.template_data) {
                
                try {
                    template = typeof product.template_data === 'string' ? JSON.parse(product.template_data) : product.template_data;
                } catch (err) {
                    
                    template = null;
                }
            } else if (product.template_json) {
                
                try {
                    template = typeof product.template_json === 'string' ? JSON.parse(product.template_json) : product.template_json;
                } catch (err) {
                    
                    template = null;
                }
            } else if (product.custom_template) {
                
                try {
                    template = typeof product.custom_template === 'string' ? JSON.parse(product.custom_template) : product.custom_template;
                } catch (err) {
                    
                    template = null;
                }
            } else {
                
                template = null;
            }

            // Heuristic scan: search all product props for a template-like object/string
            if (!template) {
                
                try {
                    const tryUnwrap = (obj) => {
                        if (!obj) return null;
                        if (Array.isArray(obj.fields)) return obj;
                        if (obj.data && Array.isArray(obj.data.fields)) return obj.data;
                        if (obj.template && Array.isArray(obj.template.fields)) return obj.template;
                        return null;
                    };
                    const keys = Object.keys(product || {});
                    for (let i = 0; i < keys.length; i++) {
                        const key = keys[i];
                        const val = product[key];
                        // Try object value
                        if (val && typeof val === 'object') {
                            const unwrapped = tryUnwrap(val);
                            if (unwrapped) {
                                
                                template = unwrapped;
                                break;
                            }
                        }
                        // Try JSON string
                        if (typeof val === 'string' && val.length > 10 && (val.includes('{') || val.includes('['))) {
                            try {
                                const parsed = JSON.parse(val);
                                const unwrapped = tryUnwrap(parsed);
                                if (unwrapped) {
                                    
                                    template = unwrapped;
                                    break;
                                }
                            } catch (e) {
                                // ignore parse errors
                            }
                        }
                    }
                } catch (e) {
                    
                }
            }
            
            // Unwrap common nesting patterns
            if (template && !Array.isArray(template.fields)) {
                if (template.data && Array.isArray(template.data.fields)) {
                    
                    template = template.data;
                } else if (template.template && Array.isArray(template.template.fields)) {
                    
                    template = template.template;
                }
            }
            
            
            if (!template || !Array.isArray(template.fields)) {
                // Fallback: fetch via the same endpoint used by the Design button
                
                jQuery.ajax({
                    url: apd_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'apd_get_customizer_data',
                        nonce: apd_ajax.nonce,
                        product_id: product.id
                    },
                    success: (response) => {
                        if (response && response.success && response.data && response.data.templateData) {
                            const td = response.data.templateData;
                            
                            // Transform templateData (canvas/elements) to our lightweight template shape
                            const mappedFields = Array.isArray(td.elements) ? td.elements
                                .filter((e) => {
                                    if (!e) return false;
                                    const t = (e.type || '').toString().toLowerCase();
                                    const isTextLike = ['text','textarea','input','label','richtext','textbox'].includes(t) || (e.properties && (e.properties.text != null));
                                    const isImageLike = ['image','img','picture','photo'].includes(t) || !!(e.url || e.src || (e.properties && (e.properties.src || e.properties.url)));
                                    return isTextLike || isImageLike;
                                })
                                .map((e, idx) => {
                                    const t = (e.type || '').toString().toLowerCase();
                                    const isTextLike = ['text','textarea','input','label','richtext','textbox'].includes(t) || (e.properties && (e.properties.text != null));
                                    const isImageLike = ['image','img','picture','photo'].includes(t) || !!(e.url || e.src || (e.properties && (e.properties.src || e.properties.url)));
                                    const fieldType = isImageLike ? 'image' : 'text';
                                    const generatedId = (e.label || ('field_' + idx)).toString().replace(/\s+/g, '_');
                                    const id = (e.id ? e.id.toString() : generatedId);
                                    const defaultText = e.properties && e.properties.text ? e.properties.text : (e.value || e.default || '');
                                    const fontSize = (e.properties && e.properties.fontSize != null) ? e.properties.fontSize : (e.fontSize != null ? e.fontSize : undefined);
                                    return {
                                        id,
                                        type: fieldType,
                                        label: e.label || (fieldType === 'image' ? ('Image ' + (idx + 1)) : ('Text ' + (idx + 1))),
                                        placeholder: e.label || '',
                                        position: {
                                            x: typeof e.x === 'number' ? e.x : 20,
                                            y: typeof e.y === 'number' ? e.y : 60,
                                            width: typeof e.width === 'number' ? e.width : undefined,
                                            height: typeof e.height === 'number' ? e.height : undefined,
                                            fontSize: fontSize
                                        },
                                        color: e.properties && e.properties.textColor ? e.properties.textColor : undefined,
                                        fontWeight: e.properties && e.properties.fontWeight ? e.properties.fontWeight : undefined,
                                        defaultValue: fieldType === 'image' ? (e.url || e.src || (e.properties && (e.properties.src || e.properties.url)) || '') : defaultText
                                    };
                                }) : [];

                            const transformedTemplate = {
                                name: product.title || 'Product Template',
                                canvas: {
                                    width: td.canvas && typeof td.canvas.width === 'number' ? td.canvas.width : 800,
                                    height: td.canvas && typeof td.canvas.height === 'number' ? td.canvas.height : 600,
                                    background: td.canvas && td.canvas.background ? td.canvas.background : { type: 'color', color: '#ffffff' }
                                },
                                logo: {
                                    type: 'image',
                                    content: product.image || product.logo_url || product.logo_file || '',
                                    position: { x: 0, y: 0, width: 400, height: 200 }
                                },
                                fields: mappedFields
                            };

                            

                            setCurrentTemplate(transformedTemplate);

                            const initialValues = {};
                            transformedTemplate.fields.forEach(f => { initialValues[f.id] = f.defaultValue || ''; });
                            setFieldValues(initialValues);
                        } else {
                            
                            setCurrentTemplate(null);
                            setFieldValues({});
                        }
                    },
                    error: (xhr, status, error) => {
                        
                        setCurrentTemplate(null);
                        setFieldValues({});
                    }
                });
                return; // Defer further handling to AJAX success
            }

            if (template && Array.isArray(template.fields)) {
                
                
                // Update logo with product logo if available
                if (product.logo_url || product.logo_file) {
                    const logoUrl = product.logo_url || product.logo_file;
                    template.logo = {
                        type: 'image',
                        content: logoUrl,
                        position: { x: 0, y: 0, width: 400, height: 200 }
                    };
                }
            
                setCurrentTemplate(template);
                
                // Initialize field values with template defaults only
                const initialValues = {};
                if (template.fields && Array.isArray(template.fields)) {
                    template.fields.forEach(field => {
                        // Only use template default values, no custom mapping
                        let value = field.defaultValue;
                        initialValues[field.id] = value;
                    });
                }
                
                setFieldValues(initialValues);
                
            } else {
                
                setCurrentTemplate(null);
                setFieldValues({});
            }
        }, []);

        // Fetch products when modal opens
        React.useEffect(() => {
            if (isModalOpen && (!products || products.length === 0)) {
                fetchProducts(searchTerm);
            }
        }, [isModalOpen, products, searchTerm, fetchProducts]);
        
        // Filter products based on search term
        React.useEffect(() => {
            if (products && products.length > 0) {
                const filtered = products.filter(product => 
                    product.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    (product.description && product.description.toLowerCase().includes(searchTerm.toLowerCase()))
                );
                setFilteredProducts(filtered);
            }
        }, [products, searchTerm]);

        const selectedProduct = products ? products.find(p => p.id == productId) : null;
        
        // Debug selected product
        

        // Load template when product is selected
        React.useEffect(() => {
            
            
            if (selectedProduct) {
                
                loadProductTemplate(selectedProduct);
            } else {
                
            }
        }, [selectedProduct, productId, loadProductTemplate]);

        const openModal = () => {
            setIsModalOpen(true);
        };
        const closeModal = () => setIsModalOpen(false);

        const selectProduct = (product) => {
            
            setAttributes({ productId: product.id });
            loadProductTemplate(product);
            closeModal();
        };

        const handleSearch = (value) => {
            setSearchTerm(value);
        };

        const handleFieldChange = React.useCallback((fieldId, value) => {
            setFieldValues(prev => ({
                ...prev,
                [fieldId]: value
            }));
        }, []);

        return el(Fragment, {}, [
            el('div', { key: 'editor', className: 'apd-product-block-editor' }, [
                selectedProduct ? 
                    el('div', { key: 'selected', className: 'apd-customizer-container' }, [
                        // Live Preview Section
                        el('div', { className: 'apd-preview-section' }, [
                            el('div', { className: 'apd-preview-title' }, __('Live Preview')),
                            el('div', { 
                                className: 'apd-preview-area',
                                ref: scrollRef,
                                onMouseDown: onMouseDownScroll,
                                onMouseLeave: onMouseLeaveScroll,
                                onMouseUp: onMouseUpScroll,
                                onMouseMove: onMouseMoveScroll,
                                style: { overflow: 'auto', cursor: isDragging ? 'grabbing' : 'grab' }
                            }, [
                                el('div', { className: 'apd-preview-content', id: 'apd-preview-content' }, 
                                    (() => {
                                        
                                        
                                        if (currentTemplate) {
                                            
                                            return TemplateRenderer.renderTemplate(currentTemplate, fieldValues);
                                        } else {
                                            
                                            return el('div', { className: 'apd-no-template' }, 'No template loaded');
                                        }
                                    })()
                                )
                            ]),
                            // Thumbnail Previews
                            el('div', { className: 'apd-thumbnails' }, [
                                el('div', { className: 'apd-thumbnail', 'data-color': 'yellow' }, [
                                    el('svg', { className: 'apd-thumbnail-svg', viewBox: '0 0 1406 682' }, [
                                        el('g', { transform: 'translate(0.000000,682.000000) scale(0.100000,-0.100000)', fill: 'currentColor', stroke: 'none' }, [
                                            el('path', { d: 'M0 3490 l0 -620 220 0 220 0 0 230 0 230 370 0 371 0 43 48 c24 26 89 99 145 162 l102 115 -516 3 -515 2 0 65 0 65 409 0 409 0 113 128 c62 70 126 142 143 160 l30 32 -772 0 -772 0 0 -620z' })
                                        ])
                                    ])
                                ]),
                                el('div', { className: 'apd-thumbnail selected', 'data-color': 'dark-red' }, [
                                    el('svg', { className: 'apd-thumbnail-svg', viewBox: '0 0 1406 682' }, [
                                        el('g', { transform: 'translate(0.000000,682.000000) scale(0.100000,-0.100000)', fill: 'currentColor', stroke: 'none' }, [
                                            el('path', { d: 'M0 3490 l0 -620 220 0 220 0 0 230 0 230 370 0 371 0 43 48 c24 26 89 99 145 162 l102 115 -516 3 -515 2 0 65 0 65 409 0 409 0 113 128 c62 70 126 142 143 160 l30 32 -772 0 -772 0 0 -620z' })
                                        ])
                                    ])
                                ]),
                                el('div', { className: 'apd-thumbnail', 'data-color': 'bright-yellow' }, [
                                    el('svg', { className: 'apd-thumbnail-svg', viewBox: '0 0 1406 682' }, [
                                        el('g', { transform: 'translate(0.000000,682.000000) scale(0.100000,-0.100000)', fill: 'currentColor', stroke: 'none' }, [
                                            el('path', { d: 'M0 3490 l0 -620 220 0 220 0 0 230 0 230 370 0 371 0 43 48 c24 26 89 99 145 162 l102 115 -516 3 -515 2 0 65 0 65 409 0 409 0 113 128 c62 70 126 142 143 160 l30 32 -772 0 -772 0 0 -620z' })
                                        ])
                                    ])
                                ]),
                                el('div', { className: 'apd-thumbnail', 'data-color': 'light-blue' }, [
                                    el('svg', { className: 'apd-thumbnail-svg', viewBox: '0 0 1406 682' }, [
                                        el('g', { transform: 'translate(0.000000,682.000000) scale(0.100000,-0.100000)', fill: 'currentColor', stroke: 'none' }, [
                                            el('path', { d: 'M0 3490 l0 -620 220 0 220 0 0 230 0 230 370 0 371 0 43 48 c24 26 89 99 145 162 l102 115 -516 3 -515 2 0 65 0 65 409 0 409 0 113 128 c62 70 126 142 143 160 l30 32 -772 0 -772 0 0 -620z' })
                                        ])
                                    ])
                                ])
                            ])
                        ]),
                        // Customization Panel
                        el('div', { className: 'apd-customization-panel' }, [
                            // Product Info
                            el('div', { className: 'apd-product-info' }, [
                                el('div', { className: 'apd-product-name' }, selectedProduct.title),
                                el('div', { className: 'apd-product-price' }, `$${selectedProduct.price} Heavy Metal Chrome with Color`)
                            ]),
                            // Dynamic Template Fields
                            ...TemplateRenderer.generatePanelInputs(currentTemplate, fieldValues, handleFieldChange),
                            // Product Features
                            el('div', { className: 'apd-form-group' }, [
                                el('h4', { style: { marginBottom: '10px', color: '#333' } }, 'Product Features:'),
                                el('ul', { style: { listStyle: 'none', padding: 0, margin: 0 } }, [
                                    el('li', { style: { padding: '5px 0', color: '#666' } }, '✓ DOT Approved Size'),
                                    el('li', { style: { padding: '5px 0', color: '#666' } }, '✓ 5 years outdoor life'),
                                    el('li', { style: { padding: '5px 0', color: '#666' } }, '✓ Air release for bubble free installing'),
                                    el('li', { style: { padding: '5px 0', color: '#666' } }, '✓ Professional quality materials')
                                ])
                            ]),
                            // Action Buttons
                            el('div', { className: 'apd-actions' }, [
                                el('input', { 
                                    type: 'number', 
                                    id: 'apd-quantity-input', 
                                    className: 'apd-quantity-input',
                                    defaultValue: 1,
                                    min: 1,
                                    max: 100
                                }),
                                el(Button, {
                                    className: 'apd-btn apd-btn-primary apd-btn-add-cart',
                                    variant: 'primary'
                                }, __('ADD TO CART')),
                                el(Button, {
                                    className: 'apd-btn apd-btn-secondary apd-btn-customize',
                                    variant: 'secondary'
                                }, __('CUSTOMIZE')),
                        el(Button, {
                                    className: 'apd-btn apd-btn-checkout',
                                    variant: 'primary'
                                }, __('CHECK OUT'))
                            ])
                        ])
                    ]) :
                    el(Placeholder, {
                        key: 'placeholder',
                        icon: 'products',
                        label: __('Product Display'),
                        instructions: __('Select a product to display on your page'),
                        className: 'apd-product-placeholder'
                    }, [
                        el(Button, {
                            onClick: openModal,
                            variant: 'primary',
                            className: 'apd-select-product-btn'
                        }, __('Select Product'))
                    ])
            ]),
            el(PanelBody, {
                key: 'settings',
                title: __('Product Settings'),
                initialOpen: true
            }, [
                el(TextControl, {
                    label: __('Product ID'),
                    value: productId || '',
                    placeholder: __('Enter product ID or click Select Product'),
                    onChange: (value) => setAttributes({ productId: parseInt(value) || 0 }),
                    help: __('Enter the product ID directly or use the Select Product button')
                }),
                el(Button, {
                    onClick: openModal,
                    variant: 'secondary',
                    className: 'apd-select-product-btn-panel',
                    style: { width: '100%', marginBottom: '15px' }
                }, __('Select Product')),
                el(SelectControl, {
                    label: __('Layout'),
                    value: layout,
                    options: [
                        { label: __('Customizer Layout'), value: 'customizer' }
                    ],
                    onChange: (value) => setAttributes({ layout: value })
                }),
                el('div', { className: 'apd-checkbox-controls' }, [
                    el('label', { className: 'apd-checkbox-label' }, [
                        el('input', {
                            type: 'checkbox',
                            checked: showPrice,
                            onChange: (e) => setAttributes({ showPrice: e.target.checked })
                        }),
                        __('Show Price')
                    ]),
                    el('label', { className: 'apd-checkbox-label' }, [
                        el('input', {
                            type: 'checkbox',
                            checked: showDescription,
                            onChange: (e) => setAttributes({ showDescription: e.target.checked })
                        }),
                        __('Show Description')
                    ])
                ])
            ]),
            el(ProductSelectorModal, {
                key: 'modal',
                isOpen: isModalOpen,
                onClose: closeModal,
                onSelect: selectProduct,
                products: filteredProducts,
                isLoading: isLoading,
                searchTerm: searchTerm,
                onSearch: handleSearch
            })
        ]);
    };

    // Product List Block Edit Component
    const ProductListBlockEdit = (props) => {
        const { attributes, setAttributes } = props;
        const { showTitle, showDescription, showPrice, showSale, columns, itemsPerPage } = attributes;

        return el(Fragment, {}, [
            el('div', { 
                key: 'preview',
                className: 'apd-product-list-preview',
                style: { 
                    padding: '20px', 
                    border: '2px dashed #ccc', 
                    borderRadius: '4px',
                    backgroundColor: '#f9f9f9'
                }
            }, [
                el('div', { 
                    style: { 
                        textAlign: 'center', 
                        marginBottom: '20px',
                        color: '#666'
                    }
                }, __('Product List Preview')),
                el('div', { 
                    style: { 
                        display: 'grid', 
                        gridTemplateColumns: `repeat(${columns}, 1fr)`,
                        gap: '15px',
                        maxWidth: '100%'
                    }
                }, [
                    el('div', { 
                        key: 'sample1',
                        style: { 
                            background: 'white', 
                            padding: '15px', 
                            borderRadius: '8px',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                        }
                    }, [
                        showTitle && el('h3', { 
                            style: { margin: '0 0 10px 0', fontSize: '16px' }
                        }, 'Sample Product 1'),
                        showDescription && el('p', { 
                            style: { margin: '0 0 10px 0', fontSize: '14px', color: '#666' }
                        }, 'Product description here...'),
                        showPrice && el('div', { 
                            style: { fontWeight: 'bold', color: '#007cba' }
                        }, showSale ? '$99.00 $125.00' : '$125.00')
                    ]),
                    el('div', { 
                        key: 'sample2',
                        style: { 
                            background: 'white', 
                            padding: '15px', 
                            borderRadius: '8px',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                        }
                    }, [
                        showTitle && el('h3', { 
                            style: { margin: '0 0 10px 0', fontSize: '16px' }
                        }, 'Sample Product 2'),
                        showDescription && el('p', { 
                            style: { margin: '0 0 10px 0', fontSize: '14px', color: '#666' }
                        }, 'Another product description...'),
                        showPrice && el('div', { 
                            style: { fontWeight: 'bold', color: '#007cba' }
                        }, '$150.00')
                    ])
                ])
            ]),
            el(PanelBody, { 
                key: 'settings',
                title: __('Product List Settings'),
                initialOpen: true
            }, [
                el(SelectControl, {
                    key: 'columns',
                    label: __('Columns'),
                    value: columns,
                    options: [
                        { label: '1 Column', value: 1 },
                        { label: '2 Columns', value: 2 },
                        { label: '3 Columns', value: 3 },
                        { label: '4 Columns', value: 4 }
                    ],
                    onChange: (value) => setAttributes({ columns: parseInt(value) })
                }),
                el(TextControl, {
                    key: 'itemsPerPage',
                    label: __('Items Per Page'),
                    type: 'number',
                    value: itemsPerPage,
                    onChange: (value) => setAttributes({ itemsPerPage: parseInt(value) || 12 })
                })
            ]),
            el(PanelBody, { 
                key: 'display',
                title: __('Display Options'),
                initialOpen: true
            }, [
                el('div', { 
                    key: 'checkboxes',
                    style: { display: 'flex', flexDirection: 'column', gap: '10px' }
                }, [
                    el('label', { 
                        key: 'title',
                        style: { display: 'flex', alignItems: 'center', gap: '8px' }
                    }, [
                        el('input', {
                            type: 'checkbox',
                            checked: showTitle,
                            onChange: (e) => setAttributes({ showTitle: e.target.checked })
                        }),
                        __('Show Product Title')
                    ]),
                    el('label', { 
                        key: 'description',
                        style: { display: 'flex', alignItems: 'center', gap: '8px' }
                    }, [
                        el('input', {
                            type: 'checkbox',
                            checked: showDescription,
                            onChange: (e) => setAttributes({ showDescription: e.target.checked })
                        }),
                        __('Show Description')
                    ]),
                    el('label', { 
                        key: 'price',
                        style: { display: 'flex', alignItems: 'center', gap: '8px' }
                    }, [
                        el('input', {
                            type: 'checkbox',
                            checked: showPrice,
                            onChange: (e) => setAttributes({ showPrice: e.target.checked })
                        }),
                        __('Show Price')
                    ]),
                    el('label', { 
                        key: 'sale',
                        style: { display: 'flex', alignItems: 'center', gap: '8px' }
                    }, [
                        el('input', {
                            type: 'checkbox',
                            checked: showSale,
                            onChange: (e) => setAttributes({ showSale: e.target.checked })
                        }),
                        __('Show Sale Price')
                    ])
                ])
            ])
        ]);
    };

    // Register the block
    registerBlockType('apd/product-list', {
        title: __('Product List'),
        icon: 'list-view',
        category: 'common',
        description: __('Display a list of products with category tabs and customizable options'),
        keywords: [__('product'), __('list'), __('shop'), __('catalog')],
        
        attributes: {
            showTitle: {
                type: 'boolean',
                default: true
            },
            showDescription: {
                type: 'boolean',
                default: true
            },
            showPrice: {
                type: 'boolean',
                default: true
            },
            showSale: {
                type: 'boolean',
                default: true
            },
            columns: {
                type: 'number',
                default: 3
            },
            itemsPerPage: {
                type: 'number',
                default: 12
            }
        },

        edit: ProductListBlockEdit,

        save: ({ attributes }) => {
            const { showTitle, showDescription, showPrice, showSale, columns, itemsPerPage } = attributes;

            return el('div', {
                className: 'apd-product-list-block',
                'data-show-title': showTitle,
                'data-show-description': showDescription,
                'data-show-price': showPrice,
                'data-show-sale': showSale,
                'data-columns': columns,
                'data-items-per-page': itemsPerPage
            });
        }
        });
        
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductBlock);
    } else {
        initProductBlock();
    }
})();
