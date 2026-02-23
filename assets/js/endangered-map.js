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

    // Status colors (IUCN-based + custom statuses)
    var statusColors = {
        'CR': { color: '#D81E05', label: 'Critically Endangered' },
        'EN': { color: '#FC7F3F', label: 'Endangered' },
        'VU': { color: '#F9E814', label: 'Vulnerable' },
        'NT': { color: '#CCE226', label: 'Near Threatened' },
        'LC': { color: '#60C659', label: 'Least Concern' },
        'DD': { color: '#D1D1C6', label: 'Data Deficient' },
        'PE': { color: '#000000', label: 'Possibly Extinct' },
        'RE': { color: '#542344', label: 'Regionally Extinct' },
        'NN': { color: '#0076AA', label: 'Not Native' },
        'NA': { color: '#FFFFFF', label: 'Not Applicable' },
        'NE': { color: '#FFFFFF', label: 'Not Evaluated' },
        'THREATENED': { color: '#E53935', label: 'Threatened' },
        'RARE': { color: '#AB47BC', label: 'Rare' },
        'P': { color: '#2E7D32', label: 'Present' },
        'A': { color: '#B0BEC5', label: 'Absent' },
        'EX': { color: '#000000', label: 'Extinct' },
        'EW': { color: '#1B0C23', label: 'Extinct in the Wild' },
        'R': { color: '#AB47BC', label: 'Rare' },
        'T': { color: '#E53935', label: 'Threatened' },
        'I': { color: '#4FC3F7', label: 'Indeterminate' },
        'K': { color: '#78909C', label: 'Insufficiently Known' },
        'S': { color: '#388E3C', label: 'Secure' },
        'V': { color: '#FFA726', label: 'Vulnerable' },
        'E': { color: '#D84315', label: 'Endangered' }
    };

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
                    if (!statusColors[a.acronym]) {
                        statusColors[a.acronym] = { color: getFallbackColor(), label: a.meaning };
                    } else {
                        statusColors[a.acronym].label = a.meaning;
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
                    var sc = statusColors[entry.status];
                    var fillColor = sc ? sc.color : '#999999';
                    activeStatuses[entry.status] = true;
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
                    var meaning = entry.meaning || acronymsMap[entry.status] || entry.status;
                    popupContent = '<strong>' + feature.properties.name + '</strong><br>' +
                        'Status: <strong>' + entry.status + '</strong> &ndash; ' + meaning;
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

    // Update legend
    function updateLegend(activeStatuses) {
        legendContainer.empty();
        var keys = Object.keys(activeStatuses);
        if (keys.length === 0) {
            legendContainer.css('display', 'none');
            return;
        }
        keys.sort();
        keys.forEach(function (code) {
            var sc = statusColors[code];
            if (!sc) return;
            var border = (sc.color === '#FFFFFF' || sc.color === '#F9E814') ? '1px solid #ccc' : 'none';
            var textColor = code === 'PE' ? '#fff' : '#333';
            legendContainer.append(
                '<div class="legend-item">' +
                    '<span class="legend-swatch" style="background-color:' + sc.color + '; border:' + border + '; color:' + textColor + ';">' + code + '</span>' +
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

    // Fetch search results
    function fetchResults() {
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

        var query = $.param({
            countries: selectedCountries,
            families: selectedFamilies,
            genera: selectedGenera,
            tribes: selectedTribes,
            statuses: selectedStatuses,
            search: searchTerm
        });

        fetch('/api/endangered/search?' + query)
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
