(function() {
    // Wait for wp.data to be available
    function initProductStore() {
        if (typeof wp === 'undefined' || !wp.data || !wp.data.registerStore) {
            setTimeout(initProductStore, 100);
            return;
        }
        
        const { registerStore } = wp.data;

    // Actions
    const actions = {
        fetchProducts(searchTerm = '') {
            return {
                type: 'FETCH_PRODUCTS',
                searchTerm
            };
        },
        receiveProducts(products) {
            return {
                type: 'RECEIVE_PRODUCTS',
                products
            };
        },
        setLoading(isLoading) {
            return {
                type: 'SET_LOADING',
                isLoading
            };
        },
        setError(error) {
            return {
                type: 'SET_ERROR',
                error
            };
        }
    };

    // Selectors
    const selectors = {
        getProducts(state) {
            return state.products || [];
        },
        isLoading(state) {
            return state.isLoading || false;
        },
        getError(state) {
            return state.error || null;
        }
    };

    // Reducer
    const reducer = (state = { products: [], isLoading: false, error: null }, action) => {
        switch (action.type) {
            case 'FETCH_PRODUCTS':
                return {
                    ...state,
                    isLoading: true,
                    error: null
                };
            case 'RECEIVE_PRODUCTS':
                return {
                    ...state,
                    products: action.products,
                    isLoading: false,
                    error: null
                };
            case 'SET_LOADING':
                return {
                    ...state,
                    isLoading: action.isLoading
                };
            case 'SET_ERROR':
                return {
                    ...state,
                    error: action.error,
                    isLoading: false
                };
            default:
                return state;
        }
    };

    // Controls
    const controls = {
        FETCH_PRODUCTS(action) {
            return new Promise((resolve, reject) => {
                // Use jQuery AJAX instead of apiFetch
                jQuery.ajax({
                    url: apd_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'apd_get_products_ajax',
                        nonce: apd_ajax.nonce,
                        search: action.searchTerm
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(actions.receiveProducts(response.data));
                        } else {
                            resolve(actions.setError(response.message || 'Failed to fetch products'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('APD: AJAX error:', error);
                        resolve(actions.setError('Network error: ' + error));
                    }
                });
            });
        }
    };

    // Resolvers
    const resolvers = {
        *getProducts() {
            const products = yield actions.fetchProducts();
            return products;
        }
    };

        // Register the store
        registerStore('apd/products', {
            reducer,
            actions,
            selectors,
            controls,
            resolvers
        });
        
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductStore);
    } else {
        initProductStore();
    }
})();
