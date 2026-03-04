$(document).ready(function () {
    if (typeof maptilersdk !== 'undefined') {
        maptilersdk.config.caching = false;
    }

    var map = L.map('map', { maxZoom: 8, minZoom: 3 });
    var key = 'EKUb4Bm0PKKlbqC26vF8';
    var loader = $('#loader');
    var resultsContainer = $('#results');
    var selectedSpeciesContainer = $('#selected-species');
    var selectedSpeciesName = $('#selected-species-name');
    var legendContainer = $('#legend');

    var geojsonLayer = null;
    var geojsonData = null;
    var currentSpecies = null;
    var acronymsMap = {};

    // Status colors for primary codes
    var statusColors = {
        'P':     { color: '#2E7D32', label: 'Present' },
        'A':     { color: '#B0BEC5', label: 'Absent' },
        'RE':    { color: '#542344', label: 'Regionally Extinct' },
        'PE':    { color: '#000000', label: 'Possibly Extinct' },
        'CR':    { color: '#D81E05', label: 'Critically Endangered' },
        'EN':    { color: '#FC7F3F', label: 'Endangered' },
        'VU':    { color: '#F9E814', label: 'Vulnerable' },
        'T':     { color: '#E53935', label: 'Threatened' },
        'NT':    { color: '#F57C00', label: 'Near Threatened' },
        'R':     { color: '#AB47BC', label: 'Rare' },
        'LC':    { color: '#60C659', label: 'Least Concern' },
        'NN':    { color: '#0076AA', label: 'Non-Native' },
        'DD':    { color: '#D1D1C6', label: 'Data Deficient' },
        'DD/LC': { color: '#D1D1C6', label: 'Data Deficient & Least Concern' },
        'NA':    { color: '#FFFFFF', label: 'Not Assessed' },
        'EX':    { color: '#000000', label: 'Extinct' },
        'EW':    { color: '#1B0C23', label: 'Extinct in the Wild' },
        'K':     { color: '#78909C', label: 'Insufficiently Known' },
        'S':     { color: '#388E3C', label: 'Secure' }
    };

    // Map variant DB codes to their primary code
    var statusNormalize = {
        'I': 'P',
        'included': 'P',
        'INCLUDED': 'P',
        'Included': 'P',
        'E': 'EN',
        'V': 'VU',
        'THREATENED': 'T',
        'THREATENED WITH EXTINCTION': 'T',
        'RARE': 'R',
        'NE': 'NA'
    };

    function normalizeStatusCode(code) {
        return statusNormalize[code] || code;
    }

    function getStatusInfo(code) {
        var normalized = normalizeStatusCode(code);
        return statusColors[normalized] || null;
    }

    // DB country name -> GeoJSON country name mapping
    var countryNameMap = {
        'Czechia': 'Czech Republic',
        'The former Yugoslav Republic of Macedonia': 'North Macedonia',
        'Republic of Moldova': 'Moldova',
        'Russian Federation': 'Russia',
        'Holy See (Vatican City)': 'Vatican City',
        'FYR Macedonia': 'North Macedonia',
        'FYROM': 'North Macedonia',
        'UK': 'United Kingdom',
        'Great Britain': 'United Kingdom',
        'Holland': 'Netherlands',
        'Bosnia & Herzegovina': 'Bosnia and Herzegovina',
        'Faroe Is.': 'Faroe Islands'
    };

    // Hide results, show hint
    hideResults();
    $('.tooltip-hint').show();

    // Init base map
    L.maptilerLayer({ apiKey: key, style: 'landscape' }).addTo(map);
    map.setView([50, 15], 4);

    // Load GeoJSON
    fetch('/plugins/pensoft/endangeredmap/assets/js/europe.geojson')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            geojsonData = data;
            addDefaultGeoJSON();
        });

    // Fallback color generator for unknown status codes
    var fallbackColorIndex = 0;
    var fallbackColors = ['#8D6E63', '#5C6BC0', '#26A69A', '#EC407A', '#7E57C2', '#FF7043', '#66BB6A', '#42A5F5'];
    function getFallbackColor() {
        var c = fallbackColors[fallbackColorIndex % fallbackColors.length];
        fallbackColorIndex++;
        return c;
    }

    // Load acronyms
    fetch('/api/endangered/acronyms')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.acronyms) {
                data.acronyms.forEach(function (a) {
                    acronymsMap[a.acronym] = a.meaning;
                    var normalized = normalizeStatusCode(a.acronym);
                    if (!statusColors[normalized]) {
                        statusColors[normalized] = { color: getFallbackColor(), label: a.meaning };
                    }
                });
            }
        });

    // Show/hide results sidebar
    function showResults() {
        resultsContainer.addClass('visible').show();
        setTimeout(function () { map.invalidateSize(); }, 50);
    }

    function hideResults() {
        resultsContainer.removeClass('visible').hide().empty();
        setTimeout(function () { map.invalidateSize(); }, 50);
    }

    // Default GeoJSON layer (uncolored)
    function addDefaultGeoJSON() {
        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }
        geojsonLayer = L.geoJSON(geojsonData, {
            style: defaultStyle,
            onEachFeature: function (feature, layer) {
                layer.bindPopup(feature.properties.name);
                layer.on('mouseover', function (e) {
                    if (!currentSpecies) {
                        e.target.setStyle({ weight: 2, fillOpacity: 0.3 });
                    }
                });
                layer.on('mouseout', function (e) {
                    if (!currentSpecies) {
                        geojsonLayer.resetStyle(e.target);
                    }
                });
            }
        }).addTo(map);
    }

    function defaultStyle() {
        return {
            fillColor: '#E0E0E0',
            weight: 1,
            opacity: 1,
            color: '#999',
            fillOpacity: 0.15
        };
    }

    function normalizeCountryName(dbName) {
        return countryNameMap[dbName] || dbName;
    }

    // Color GeoJSON by species statuses
    function colorMap(statuses) {
        var countryStatusMap = {};
        statuses.forEach(function (s) {
            var geoName = normalizeCountryName(s.country);
            countryStatusMap[geoName.toLowerCase()] = s;
        });

        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        var activeStatuses = {};

        geojsonLayer = L.geoJSON(geojsonData, {
            style: function (feature) {
                var name = feature.properties.name.toLowerCase();
                var entry = countryStatusMap[name];
                if (entry) {
                    var normalized = normalizeStatusCode(entry.status);
                    var sc = statusColors[normalized];
                    var fillColor = sc ? sc.color : '#999999';
                    activeStatuses[normalized] = true;
                    return {
                        fillColor: fillColor,
                        weight: 1,
                        opacity: 1,
                        color: '#666',
                        fillOpacity: 0.7
                    };
                }
                return defaultStyle();
            },
            onEachFeature: function (feature, layer) {
                var name = feature.properties.name.toLowerCase();
                var entry = countryStatusMap[name];
                var popupContent;
                if (entry) {
                    var normalized = normalizeStatusCode(entry.status);
                    var sc = statusColors[normalized];
                    var meaning = entry.meaning || acronymsMap[entry.status] || (sc ? sc.label : entry.status);
                    popupContent = '<strong>' + feature.properties.name + '</strong><br>' +
                        'Status: <strong>' + meaning + '</strong>';
                } else {
                    popupContent = feature.properties.name + '<br><em>No data</em>';
                }
                layer.bindPopup(popupContent);
                layer.on('mouseover', function (e) {
                    e.target.setStyle({ weight: 3, fillOpacity: 0.9 });
                    if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
                        e.target.bringToFront();
                    }
                });
                layer.on('mouseout', function (e) {
                    geojsonLayer.resetStyle(e.target);
                });
            }
        }).addTo(map);

        updateLegend(activeStatuses);
    }

    // Legend display order (matches the filter order)
    var legendOrder = [
        'P', 'A', 'RE', 'PE', 'CR', 'EN', 'VU', 'T', 'NT',
        'R', 'LC', 'NN', 'DD', 'DD/LC', 'NA', 'EX', 'EW', 'K', 'S'
    ];

    // Update legend
    function updateLegend(activeStatuses) {
        legendContainer.empty();
        var keys = Object.keys(activeStatuses);
        if (keys.length === 0) {
            legendContainer.css('display', 'none');
            return;
        }
        // Sort by legendOrder position, unknowns at end
        var orderMap = {};
        legendOrder.forEach(function (code, i) { orderMap[code] = i; });
        keys.sort(function (a, b) {
            return (orderMap[a] !== undefined ? orderMap[a] : 999) - (orderMap[b] !== undefined ? orderMap[b] : 999);
        });
        keys.forEach(function (code) {
            var sc = statusColors[code];
            if (!sc) return;
            var border = (sc.color === '#FFFFFF' || sc.color === '#F9E814' || sc.color === '#D1D1C6') ? '1px solid #ccc' : 'none';
            var textColor = (code === 'PE' || code === 'RE' || code === 'EW') ? '#fff' : '#333';
            legendContainer.append(
                '<div class="legend-item">' +
                    '<span class="legend-swatch" style="background-color:' + sc.color + '; border:' + border + ';"></span>' +
                    '<span class="legend-label">' + sc.label + '</span>' +
                '</div>'
            );
        });
        legendContainer.css('display', 'flex');
    }

    // Select a species and color the map
    function selectSpecies(name) {
        currentSpecies = name;
        selectedSpeciesName.text(name);
        selectedSpeciesContainer.show();
        $('.tooltip-hint').hide();
        loader.show();

        // Highlight active item in results
        resultsContainer.find('li').removeClass('active');
        resultsContainer.find('li').each(function () {
            if ($(this).text() === name) {
                $(this).addClass('active');
            }
        });

        fetch('/api/endangered/species?q=' + encodeURIComponent(name))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                loader.hide();
                if (data.statuses) {
                    colorMap(data.statuses);
                }
            })
            .catch(function () {
                loader.hide();
            });
    }

    // Clear species selection
    function clearSpecies() {
        currentSpecies = null;
        selectedSpeciesContainer.hide();
        selectedSpeciesName.text('');
        $('.tooltip-hint').show();
        legendContainer.empty().css('display', 'none');
        resultsContainer.find('li').removeClass('active');
        addDefaultGeoJSON();
    }

    // Fetch search results with debounce and request cancellation
    var searchDebounceTimer = null;
    var currentAbortController = null;

    function fetchResults() {
        if (searchDebounceTimer) {
            clearTimeout(searchDebounceTimer);
        }
        searchDebounceTimer = setTimeout(doFetchResults, 250);
    }

    function doFetchResults() {
        var selectedCountries = $('#country-select').val() || [];
        var selectedFamilies = $('#family-select').val() || [];
        var selectedGenera = $('#genus-select').val() || [];
        var selectedTribes = $('#tribe-select').val() || [];
        var selectedStatuses = $('#status-select').val() || [];
        var searchTerm = $('#species-search').val().trim();

        if (!searchTerm && selectedCountries.length === 0 && selectedFamilies.length === 0 &&
            selectedGenera.length === 0 && selectedTribes.length === 0 && selectedStatuses.length === 0) {
            hideResults();
            return;
        }

        // Cancel any in-flight request
        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();

        var query = $.param({
            countries: selectedCountries,
            families: selectedFamilies,
            genera: selectedGenera,
            tribes: selectedTribes,
            statuses: selectedStatuses,
            search: searchTerm
        });

        fetch('/api/endangered/search?' + query, { signal: currentAbortController.signal })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                resultsContainer.empty();
                if (data.species && data.species.length > 0) {
                    resultsContainer.append(
                        '<div class="results-header">' + data.results + ' species found</div>'
                    );
                    var list = $('<ul>');
                    data.species.forEach(function (name) {
                        var li = $('<li>').text(name);
                        li.on('click', function (e) {
                            e.preventDefault();
                            selectSpecies(name);
                        });
                        if (currentSpecies === name) {
                            li.addClass('active');
                        }
                        list.append(li);
                    });
                    resultsContainer.append(list);
                    showResults();
                } else {
                    resultsContainer.append('<div class="results-header">No species found</div>');
                    showResults();
                }
            })
            .catch(function (err) {
                if (err.name !== 'AbortError') {
                    resultsContainer.empty();
                    resultsContainer.append('<div class="results-header">Search error. Please try again.</div>');
                    showResults();
                }
            });
    }

    // Event handlers
    $('#clear-species').on('click', clearSpecies);

    $('#species-search').on('input', fetchResults);

    $('#reset-filters-button').on('click', function () {
        $('#species-search').val('');
        ['#status-select', '#country-select', '#family-select', '#genus-select', '#tribe-select'].forEach(function (sel) {
            var el = $(sel)[0];
            if (el && el.selectize) {
                el.selectize.clear();
            }
        });
        hideResults();
        clearSpecies();
    });

    // Init selectize
    $('#status-select, #country-select, #family-select, #genus-select, #tribe-select').selectize({
        plugins: ['remove_button'],
        onChange: fetchResults
    });
});
