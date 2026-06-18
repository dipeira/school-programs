$(document).ready(function() {
    const formatDate = inputDate => inputDate.split('-').reverse().join('/');
    var deletedImagesTrack = [];

    function confirmDelete(recordId) {
        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Είστε σίγουροι;',
            text: "Δεν είναι δυνατή η μετέπειτα ανάκτηση!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ναι, προχώρησε!',
            cancelButtonText: 'Ακύρωση'
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with the delete action if confirmed
                deleteRecord(recordId);
            }
        });
    }

    // Attach to window object for global access
    window.confirmDelete = confirmDelete;

    function deleteRecord(recordId) {
        // Perform the delete action using fetch
        fetch('db.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `delete_id=${recordId}`,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Επιτυχία!',
                        'Η εγγραφή διαγράφηκε.',
                        'success'
                    ).then(() => {
                        location.reload(); // Refresh the page or update the UI
                    });
                } else {
                    Swal.fire(
                        'Σφάλμα!',
                        'Αποτυχία διαγραφής εγγραφής: ' + data.error,
                        'error'
                    );
                }
            })
            .catch(error => {
                Swal.fire(
                    'Σφάλμα!',
                    'Προέκυψε ένα σφάλμα: ' + error.message,
                    'error'
                );
            });
    }


    // Initialize DataTable safely
    if ($.fn.DataTable && $('#progs').length) {
        $('#progs').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/el.json',
            },
            columnDefs: [
                {
                    targets: [1, 2],
                    className: 'noVis'
                },
                {
                    targets: 7,
                    type: 'num' // Force strict mathematical sorting so data-order integer isn't parsed as text
                }
            ],
            order: [[7, 'desc']], // Dramatically improves UX: Native load auto-sorts to the newest programs instantly
            layout: {
                topStart: {
                    buttons: [
                        {
                            extend: 'colvis',
                            columns: ':not(.noVis)',
                            popoverTitle: 'Επιλογή ορατών στηλών'
                        },
                        'excelHtml5',
                        {
                            extend: 'pdfHtml5',
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: {
                                columns: ':not(:last-child)'
                            }
                        }
                    ]
                }
            }
        });
    }

    if ($.fn.datepicker && $('.datepicker').length) {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
        });
    }


    // Initialize Select2 elements safely
    if ($.fn.select2) {
        if ($('#sch1').length) {
            $('#sch1').select2({
                ajax: {
                    url: 'db.php?all_schools=true', // The URL to retrieve the options
                    dataType: 'json',
                    delay: 500,
                    processResults: function(data) { return { results: data }; },
                    cache: true
                },
                minimumInputLength: 0, // You can adjust this according to your needs
                dropdownParent: $('#editForm')
            });
        }

        if ($('#sch2').length) {
            $('#sch2').select2({
                ajax: {
                    url: 'db.php?all_schools=true', // The URL to retrieve the options
                    dataType: 'json',
                    delay: 500,
                    processResults: function(data) { return { results: data }; },
                    cache: true
                },
                minimumInputLength: 0, // You can adjust this according to your needs
                dropdownParent: $('#editForm')
            });
        }
    }


    // Function to display Bootstrap alert
    function showAlert(message, type) {
        var alertClass = 'alert-' + type; // Bootstrap alert class
        var alertHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                            message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
        $('#alertContainer').append(alertHTML); // Append alert to container
    }


    function replaceSelect2(schId){
        var $sch1 = $('#sch1');
        // Get the school name from db
        $.get('db.php', { sch_id: schId }, function(data) { 
            // Create a new span element with the school name
            var $customTextElement = $('<span>&nbsp;&nbsp;&nbsp;<b>' + data + '</b></span>');
            // Append the custom text element to the same location where the Select2 element was
            $customTextElement.insertAfter($sch1);
            // Create a hidden input element
            var $hiddenInput = $('<input type="hidden" name="sch1" value="' + schId + '">');
            // Append the hidden input after the custom text element
            $hiddenInput.insertAfter($customTextElement);
            // Remove the Select2 element completely
            if ($sch1.data('select2')) {
                $sch1.select2('destroy');
            }
            $sch1.remove();
            $('#editForm')[0].reset();
        });   
    }

    $('.add-record').on('click', function() {
        // Use AJAX to get record details and populate the view modal
        // Show the view modal
        $('#editModal').modal('show');
        // make school tab active
        $('#editTabs a[href="#school"]').tab('show');
        // enable all inputs (in case they're disabled)
        $("#editForm :input").prop("disabled", false);
        
        // Reset catalog specific fields
        deletedImagesTrack = [];
        $('#deleted_images').val('[]');
        $('#publish_images_preview').empty();
        $('#publish_files').val('');
        $('#publish_check').prop('checked', false);
        $('#publish_input').val('Όχι');
        $('#catalog_fields_wrapper').hide();
        $('#publish_text').val('');
        updateWordCount();

        // get sch id (if not admin)
        var schId = $(this).data('schid');
        if (!!schId && schId != 1) {
            replaceSelect2(schId);
        } else {
            $('#editForm')[0].reset();
        }
    });


    // Edit / View button click event handler
    $('#progs').on('click', '.edit-record, .view-record', function() {
        var triggeredClasses = $(this).attr('class').split(' ');
        var triggeredClass = triggeredClasses[2];
        
        // Extract record ID and fetch details from the server
        var recordId = $(this).data('record-id');
        var schId = $(this).data('sch-id');
        var lockBasic = $(this).data('lock-basic');
        var isAdmin = $(this).data('admin');
        var archiveYear = $(this).data('year'); // Dynamic routing

        // Reset catalog inputs on load
        deletedImagesTrack = [];
        $('#deleted_images').val('[]');
        $('#publish_images_preview').empty();
        $('#publish_files').val('');

        // Use AJAX to get record details and populate the edit modal
        $.get('db.php', { id: recordId, year: archiveYear }, function(data) { 
            $.each(data, function(key, value) {
                var fieldId = key;
                var $field = $('#' + fieldId);
                
                // Set the value of the form field
                if (key === 'praxidate') {
                    $field.val(formatDate(value));
                } else if ($field.length) {
                    $field.val(value);
                }
                
                if (fieldId === 'sch1') {
                    if ($.fn.select2 && $field.data('select2')) {
                        var $select2 = $field.data('select2');
                        var $option = $select2.$element.find('option[value="' + value + '"]');
                        
                        if ($option.length === 0) {
                            $field.append(new Option(data.sch1name, value, true, true)).trigger('change');
                        } else {
                            $field.val(value).trigger('change');
                        }
                    }
                    // if school, disable sch1
                    if (schId > 0) {
                        $("#sch1").prop("disabled", true);
                    }
                }
            });

            // Map publication checkbox state (Greek 'Ναι' in unicode is \u039d\u03b1\u03b9)
            var isPublished = (data.publish === 'Ναι' || data.publish === '\u039d\u03b1\u03b9');
            $('#publish_check').prop('checked', isPublished);
            $('#publish_input').val(isPublished ? 'Ναι' : 'Όχι');
            if (isPublished) {
                $('#catalog_fields_wrapper').show();
            } else {
                $('#catalog_fields_wrapper').hide();
            }

            // Word counter update
            var descText = data.publish_text || '';
            $('#publish_text').val(descText);
            updateWordCount();

            // Render existing images
            var existingImages = [];
            try {
                if (data.publish_images) {
                    existingImages = JSON.parse(data.publish_images);
                }
            } catch (e) {
                console.error("Error parsing publish_images JSON", e);
            }
            
            var $preview = $('#publish_images_preview');
            $preview.empty();
            if (Array.isArray(existingImages)) {
                $.each(existingImages, function(index, imgPath) {
                    var $wrapper = $('<div class="img-preview-wrapper" data-path="' + imgPath + '"></div>');
                    $wrapper.append('<img src="' + imgPath + '" alt="Preview">');
                    var $removeBtn = $('<button type="button" class="remove-img-btn"><i class="bi bi-trash"></i></button>');
                    $wrapper.append($removeBtn);
                    $preview.append($wrapper);
                });
            }
        });

        // if view, disable all inputs
        if (triggeredClass === 'view-record') {
            $("#editForm :input").prop("disabled", true);
            $('.modal-title').text('Προβολή προγράμματος');
            $('.save-btn').hide();
            $('.close-btn').prop("disabled", false);
        } else {
            $("#editForm :input").prop("disabled", false);
            // disable #vev if canVev is not set
            if (!$(this).data('canvev')){
                $("#vev").prop("disabled", true);
            }
            $('.modal-title').text('Επεξεργασία προγράμματος');
            $('.save-btn').show();
            $('.close-btn').prop("disabled", false);
            if (lockBasic && !isAdmin) {
                // disable basic fields
                var inputsToDisable = ['sch1', 'sch2', 'titel', 'nam1', 'nam2', 'nam3', 'eid1', 'eid2', 'eid3'];
                $.each(inputsToDisable, function(index, id) {
                    $('#' + id).prop('disabled', true);
                });
            }
        }
    
        // Show the edit modal
        $('#editModal').modal('show');
        // make school tab active
        $('#editTabs a[href="#school"]').tab('show');
    });

    // Handle Publish Settings checkbox toggle
    $(document).on('change', '#publish_check', function() {
        var checked = $(this).is(':checked');
        $('#publish_input').val(checked ? 'Ναι' : 'Όχι');
        if (checked) {
            $('#catalog_fields_wrapper').slideDown();
        } else {
            $('#catalog_fields_wrapper').slideUp();
        }
    });

    // Handle Live Word Count
    function updateWordCount() {
        var text = $('#publish_text').val() || '';
        var words = text.trim().split(/\s+/).filter(Boolean);
        var count = words.length;
        var $label = $('#word_count_label');
        $label.text('(' + count + ' / 800 λέξεις)');
        if (count > 800) {
            $label.removeClass('text-muted').addClass('text-danger fw-bold');
        } else {
            $label.removeClass('text-danger fw-bold').addClass('text-muted');
        }
    }
    $(document).on('input propertychange', '#publish_text', updateWordCount);

    // Validate size limit (max 8MB per image)
    $(document).on('change', '#publish_files', function() {
        var files = this.files;
        var limitExceeded = false;
        for (var i = 0; i < files.length; i++) {
            if (files[i].size > 8388608) { // 8MB
                limitExceeded = true;
                break;
            }
        }
        if (limitExceeded) {
            Swal.fire({
                title: 'Προσοχή!',
                text: 'Κάποιο αρχείο υπερβαίνει το όριο των 8MB! Παρακαλούμε επιλέξτε μικρότερα αρχεία.',
                icon: 'warning',
                confirmButtonText: 'Εντάξει'
            });
            $(this).val('');
        }
    });

    // Handle existing image removal
    $(document).on('click', '.remove-img-btn', function() {
        var $wrapper = $(this).closest('.img-preview-wrapper');
        var path = $wrapper.data('path');
        deletedImagesTrack.push(path);
        $('#deleted_images').val(JSON.stringify(deletedImagesTrack));
        $wrapper.remove();
    });

    // editForm submit handler
    $('#editForm').submit(function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Enforce word count limit on submit if publish enabled
        if ($('#publish_check').is(':checked')) {
            var text = $('#publish_text').val() || '';
            var words = text.trim().split(/\s+/).filter(Boolean);
            if (words.length > 800) {
                Swal.fire(
                    'Σφάλμα!',
                    'Η παρουσίαση του προγράμματος δεν πρέπει να υπερβαίνει τις 800 λέξεις!',
                    'error'
                );
                return;
            }
        }

        // Use FormData to support file uploads
        var formData = new FormData(this);

        // Perform an AJAX POST request to db.php to save the edited data
        $.ajax({
            url: 'db.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire(
                        'Επιτυχία!',
                        'Επιτυχής αποθήκευση',
                        'success'
                    ).then(() => {
                        location.reload(); // Refresh the page or update the UI
                    });
                } else {
                    showAlert('Σφάλμα: ' + response.error, 'error');
                }
            },
            error: function(err) {
                console.log(err.responseText);
                showAlert('Σφάλμα αποθήκευσης...', 'error');
            }
        });
    });

    // Handle Archive Form Submission
    $('#archiveForm').submit(function(event) {
        event.preventDefault();
        
        var yearSuffix = $('#archive_year_suffix').val();
        var yearRegex = /^\d{4}-\d{2}$/;
        if (!yearRegex.test(yearSuffix)) {
            Swal.fire('Σφάλμα!', 'Παρακαλούμε χρησιμοποιήστε τη μορφή ΕΕΕΕ-ΕΕ (π.χ. 2024-25)', 'error');
            return;
        }

        if (!$('#confirmArchive').is(':checked')) {
            showAlert('You must confirm the destructive action.', 'error');
            return;
        }

        var formData = $(this).serialize();

        $.ajax({
            url: 'db.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire(
                        'Επιτυχία!',
                        'H αρχειοθέτηση του έτους ολοκληρώθηκε επιτυχώς! Ο κενός πίνακας δημιουργήθηκε.',
                        'success'
                    ).then(() => {
                        window.location.href = 'index.php'; // Force jump to clean table
                    });
                } else {
                    Swal.fire('Σφάλμα!', response.error, 'error');
                }
            },
            error: function(err) {
                console.log(err.responseText);
                var errorMsg = err.responseText ? ' (Server Response: ' + err.responseText.substring(0, 500) + ')' : '';
                Swal.fire('Σφάλμα!', 'Αποτυχία επικοινωνίας με τη βάση δεδομένων.' + errorMsg, 'error');
            }
        });
    });

    // Handle Restore Form Submission
    $('#restoreForm').submit(function(event) {
        event.preventDefault();
        
        if (!$('#confirmRestore').is(':checked')) {
            showAlert('You must confirm the destructive action.', 'error');
            return;
        }

        var formData = $(this).serialize();

        $.ajax({
            url: 'db.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire(
                        'Επιτυχία Επαναφοράς!',
                        'Ο επιλεγμένος πίνακας αρχείου ανακτήθηκε και έγινε ξανά ο τρέχων ενεργός πίνακας!',
                        'success'
                    ).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('Σφάλμα!', response.error, 'error');
                }
            },
            error: function(err) {
                console.log(err.responseText);
                var errorMsg = err.responseText ? ' (Server Response: ' + err.responseText.substring(0, 500) + ')' : '';
                Swal.fire('Σφάλμα!', 'Αποτυχία επικοινωνίας με τη βάση δεδομένων.' + errorMsg, 'error');
            }
        });
    });

    var metadataState = {}; // Global metadata cache for protocols

    // load parameters from config.json to #configModal
    function loadConfigData() {
        console.log("Loading configuration data...");
        var $general = $('#general-settings');
        var $selectYear = $('#selectMetadataYear');
        var $inputsDiv = $('#yearProtocolInputs');

        if (!$general.length || !$selectYear.length) {
            console.error("Critical Error: Modal tab containers not found in DOM.");
            return;
        }

        $general.html('<p class="text-muted small">Φορτώνει...</p>');
        $selectYear.html('<option value="">Φορτώνει...</option>');
        $inputsDiv.addClass('d-none');

        // Load General Settings
        $.ajax({
            url: "config.json",
            dataType: "json",
            cache: false,
            success: function(data) {
                console.log("General settings loaded:", data);
                $general.empty();
                $.each(data, function(index, setting) {
                    var inputHtml = '';
                    if (typeof setting.value === 'boolean') {
                        inputHtml = '<div class="mb-3">' +
                                        '<input class="form-check-input" type="checkbox" id="' + setting.name + '" ' + (setting.value ? 'checked' : '') + '>&nbsp;' +
                                        '<label class="form-check-label" for="' + setting.name + '">' + setting.description + '</label>' +
                                    '</div>';
                    } else {
                        inputHtml = '<div class="mb-3">' +
                                        '<label for="' + setting.name + '" class="form-label">' + setting.description + '</label>&nbsp;' +
                                        '<input type="text" class="form-control" id="' + setting.name + '" value="' + setting.value + '">' +
                                    '</div>';
                    }
                    $general.append(inputHtml);
                });
            },
            error: function(err) {
                console.error("Error loading config.json:", err);
                $general.html('<p class="text-danger">Σφάλμα φόρτωσης config.json</p>');
            }
        });

        // Load Year Metadata
        $.ajax({
            url: 'db.php',
            data: { action: 'get_metadata' },
            dataType: 'json',
            success: function(metaData) {
                console.log("Metadata loaded successfully:", metaData);
                metadataState = {}; // Reset cache
                $selectYear.empty().append('<option value="">Επιλέξτε έτος...</option>');
                
                var allYears = [];
                // Sync with Metadata table (Source of Truth)
                $.each(metaData, function(i, meta) {
                    if (meta.year_name && allYears.indexOf(meta.year_name) === -1) {
                        allYears.push(meta.year_name);
                    }
                });

                // Get current year from hidden input
                var currentSxetos = $('#currentSxetos').val();
                if (currentSxetos && allYears.indexOf(currentSxetos) === -1) {
                    allYears.push(currentSxetos);
                }
                
                // Also get available past years
                var availableStr = $('#availableYearsHidden').val();
                if (availableStr) {
                    var pastYears = availableStr.split(',');
                    $.each(pastYears, function(i, py) {
                        if (py && allYears.indexOf(py) === -1) {
                            allYears.push(py);
                        }
                    });
                }

                allYears.sort().reverse();

                $.each(allYears, function(i, year) {
                    $selectYear.append('<option value="' + year + '">' + year + '</option>');
                    // Find existing record or create empty
                    var record = { protocol: '', protocol_date: '' };
                    for (var j = 0; j < metaData.length; j++) {
                        if (metaData[j].year_name === year) {
                            record = { 
                                protocol: metaData[j].protocol || '', 
                                protocol_date: metaData[j].protocol_date || '' 
                            };
                            break;
                        }
                    }
                    metadataState[year] = record;
                });
                
                if (allYears.length === 0) {
                    $selectYear.html('<option value="">Δεν βρέθηκαν έτη</option>');
                }
            },
            error: function(err) {
                console.error("Error loading metadata from db.php:", err);
                $selectYear.html('<option value="">Σφάλμα φόρτωσης</option>');
            }
        });
    }

    // Change listener for year selector
    $(document).on('change', '#selectMetadataYear', function() {
        var year = $(this).val();
        var $inputsDiv = $('#yearProtocolInputs');
        if (year && metadataState[year]) {
            $('#meta_p_num').val(metadataState[year].protocol);
            $('#meta_p_date').val(metadataState[year].protocol_date);
            $inputsDiv.removeClass('d-none');
        } else {
            $inputsDiv.addClass('d-none');
        }
    });

    // Input listeners to update cache
    $(document).on('input', '#meta_p_num, #meta_p_date', function() {
        var year = $('#selectMetadataYear').val();
        if (year && metadataState[year]) {
            metadataState[year].protocol = $('#meta_p_num').val();
            metadataState[year].protocol_date = $('#meta_p_date').val();
        }
    });

    // Create New Year Button logic
    $(document).on('click', '#btnCreateNextYear', function() {
        var years = Object.keys(metadataState).filter(function(y) { 
            return /^\d{4}-\d{2}$/.test(y); 
        });
        
        if (years.length === 0) {
            alert("Δεν βρέθηκαν υπάρχοντα έτη για υπολογισμό του επόμενου.");
            return;
        }

        years.sort().reverse();
        var maxYear = years[0]; // e.g. "2025-26"
        
        var parts = maxYear.split('-');
        if (parts.length !== 2) return;

        var nextPart1 = parseInt(parts[0]) + 1;
        var nextPart2 = parseInt(parts[1]) + 1;
        var suffixStr = nextPart2.toString().padStart(2, '0');
        var nextYear = nextPart1 + '-' + suffixStr;

        if (metadataState[nextYear]) {
            alert("Το έτος " + nextYear + " υπάρχει ήδη.");
            $('#selectMetadataYear').val(nextYear).trigger('change');
            return;
        }

        // Add to state
        metadataState[nextYear] = { protocol: '', protocol_date: '' };
        
        // Update dropdown
        var $selectYear = $('#selectMetadataYear');
        $selectYear.prepend('<option value="' + nextYear + '">' + nextYear + '</option>');
        $selectYear.val(nextYear).trigger('change');
        
        console.log("Created next year entries for:", nextYear);
    });

    // save parameters from modal to file
    function saveConfigData() {
        var configData = [];
        var yearMetadata = [];
    
        // Collect General Settings
        $('#general-settings input').each(function() {
            var name = $(this).attr('id');
            var description = $(this).closest('.mb-3').find('label').text();
            var value = $(this).is(':checkbox') ? $(this).prop('checked') : $(this).val();
    
            configData.push({
                name: name,
                description: description,
                value: value
            });
        });

        // Collect Year Protocols from Cache
        for (var year in metadataState) {
            yearMetadata.push({
                year_name: year,
                protocol: metadataState[year].protocol,
                protocol_date: metadataState[year].protocol_date
            });
        }
    
        // Save General Settings
        $.ajax({
            type: "POST",
            url: "save_config.php",
            data: { configData: JSON.stringify(configData) },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Save Year Metadata if successful
                    $.ajax({
                        type: "POST",
                        url: "db.php",
                        data: { 
                            action: 'save_metadata', 
                            metadata: JSON.stringify(yearMetadata) 
                        },
                        dataType: "json",
                        success: function(res) {
                            if (res.success) {
                                showAlert("Επιτυχής αποθήκευση παραμέτρων!", 'success');
                                $('#configModal').modal('hide');
                            } else {
                                showAlert("Σφάλμα αποθήκευσης πρωτοκόλλων.", 'danger');
                            }
                        }
                    });
                } else {
                    showAlert("Σφάλμα αποθήκευσης παραμέτρων.", 'danger');
                }
            }
        });
    }

    // Load configuration data when the modal is shown
    $('#configModal').on('shown.bs.modal', function() {
        loadConfigData(); // Load configuration data
    });

    // Save configuration data when Save Changes button is clicked
    $('#saveConfigBtn').on('click', function() {
        saveConfigData(); // Save configuration data
    });

    // call export.php & create xlsx file with all table data
    $('#exportButton').on('click', function(event) {
        event.preventDefault();
        var archYear = $(this).data('year');
        var exportUrl = 'export.php' + (archYear ? '?year=' + archYear : '');
        
        $.ajax({
            url: exportUrl,
            method: 'GET',
            success: function(response) {
                var res = JSON.parse(response);
                var baseUrl = window.location.origin + window.location.pathname; // Get the base URL of your page
                var fullUrl = baseUrl + res.fileUrl; // Concatenate the base URL with the file URL
                window.open(fullUrl, '_blank'); // Open the full URL in a new tab
            },
            error: function() {
                console.error('Error exporting data');
            }
        });
    });

    // Refresh the page when the modal is closed to sync table
    $('#editModal').on('hidden.bs.modal', function () {
        if (!$('.print-btn').hasClass('d-none')) {
            location.reload();
        }
    });

    // Print Program Handler
    $('#printProgramBtn').on('click', function() {
        var printContents = "";
        var schoolName = $('#sch_name').val() || "Σχολείο";
        var programTitle = $('#titel').val() || "Πρόγραμμα";

        printContents += "<div id='print-area' style='padding: 20px; font-family: sans-serif;'>";
        printContents += "<div style='text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px;'>";
        printContents += "<h2>Σχέδιο Προγράμματος Σχολικών Δραστηριοτήτων</h2>";
        printContents += "<h4>" + schoolName + "</h4>";
        printContents += "</div>";

        $('#editForm .tab-pane').each(function() {
            var tabId = $(this).attr('id');
            // Skip publish tab in printable output
            if (tabId === 'publish') return;
            
            var tabLabel = $("a[href='#" + tabId + "']").text().trim();
            
            printContents += "<div style='margin-bottom: 20px;'>";
            printContents += "<h3 style='background: #f0f0f0; padding: 5px; border-left: 5px solid #333;'>" + tabLabel + "</h3>";
            
            $(this).find('div.form-group, div.mb-3').each(function() {
                var labelText = $(this).find('label').text().trim();
                var $input = $(this).find('input, textarea, select');
                var val = "";

                if ($input.is('select')) {
                    val = $input.find('option:selected').text();
                } else {
                    val = $input.val();
                }

                if (labelText && val && val !== "0" && val !== "") {
                    printContents += "<div style='margin: 8px 0; border-bottom: 1px dotted #ccc; padding-bottom: 3px;'>";
                    printContents += "<strong>" + labelText + ":</strong> <span style='margin-left: 10px;'>" + val + "</span>";
                    printContents += "</div>";
                }
            });
            printContents += "</div>";
        });
        printContents += "<div style='margin-top: 30px; text-align: right; font-style: italic; font-size: 0.8em;'>Ημερομηνία Εκτύπωσης: " + new Date().toLocaleString() + "</div>";
        printContents += "</div>";

        var p    // ==========================================
    // Public Catalog Script Logic
    // ==========================================
    var currentCatalogData = [];

    function stripGreekAccents(str) {
        if (!str) return '';
        return String(str).normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    }

    function renderCatalog() {
        var searchTerm = stripGreekAccents($('#catalogSearchInput').val() || '').trim();
        
        // Show/hide clear button
        if (searchTerm.length > 0) {
            $('#btn_clear_search').removeClass('d-none');
        } else {
            $('#btn_clear_search').addClass('d-none');
        }

        var filteredData = currentCatalogData.filter(function(item) {
            if (!searchTerm) return true;
            
            var schoolName = stripGreekAccents(item.school_name || '');
            var programTitle = stripGreekAccents(item.titel || '');
            
            return schoolName.indexOf(searchTerm) !== -1 || programTitle.indexOf(searchTerm) !== -1;
        });

        $('#catalog_items').empty();
        $('#catalog_empty').hide();

        if (filteredData.length === 0) {
            $('#catalog_empty').show();
            return;
        }

        $.each(filteredData, function(index, item) {
            var images = [];
            try {
                if (item.publish_images) {
                    images = JSON.parse(item.publish_images);
                }
            } catch (e) {}

            var firstImg = (images && images.length > 0) ? images[0] : 'https://placehold.co/600x400/ecefe6/333333?text=Πρόγραμμα+Δραστηριοτήτων';

            var cardHtml = 
                '<div class="col">' +
                    '<div class="card catalog-card h-100">' +
                        '<img src="' + firstImg + '" class="card-img-top" alt="Program Image" style="height: 180px; object-fit: cover;">' +
                        '<div class="card-body d-flex flex-column">' +
                            '<div class="school-name">' + htmlEntities(item.school_name) + '</div>' +
                            '<h5 class="program-title">' + htmlEntities(item.titel) + '</h5>' +
                            '<div class="mt-auto">' +
                                '<span class="badge bg-primary mb-2">' + htmlEntities(item.categ) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="card-footer bg-light">' +
                            '<button type="button" class="btn btn-primary btn-sm btn-view-program w-100" data-pid="' + item.pid + '"><i class="bi bi-eye me-1"></i>Προβολή</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            $('#catalog_items').append(cardHtml);
        });

        // Display Details Click Handler
        $('.btn-view-program').off('click').on('click', function() {
            var pid = $(this).data('pid');
            var matched = null;
            for (var i = 0; i < currentCatalogData.length; i++) {
                if (currentCatalogData[i].pid == pid) {
                    matched = currentCatalogData[i];
                    break;
                }
            }

            if (matched) {
                $('#detail_school_badge').text(matched.school_name);
                $('#detail_program_title').text(matched.titel);
                $('#detail_category_badge').text(matched.categ);
                $('#detail_description_text').text(matched.publish_text || '');

                var detailImages = [];
                try {
                    if (matched.publish_images) {
                        detailImages = JSON.parse(matched.publish_images);
                    }
                } catch (e) {}

                var $gallery = $('#detail_images_grid');
                $gallery.empty();
                if (detailImages && detailImages.length > 0) {
                    $('#detail_images_section').show();
                    $.each(detailImages, function(idx, src) {
                        var imgCol = 
                            '<div class="col">' +
                                '<img src="' + src + '" class="img-fluid detail-gallery-img" alt="Gallery Image">' +
                            '</div>';
                        $gallery.append(imgCol);
                    });
                } else {
                    $('#detail_images_section').hide();
                }

                // Transition views
                $('#catalog_list_view').fadeOut(200, function() {
                    $('#catalog_detail_view').fadeIn(200);
                    $('html, body').animate({
                        scrollTop: $('#catalog_detail_view').offset().top - 20
                    }, 300);
                });
            }
        });
    }

    if ($('#catalog_items').length) {
        var defaultYear = $('#publicYearSelect').val() || '';
        loadPublicCatalog(defaultYear);
    }

    $(document).on('change', '#publicYearSelect', function() {
        var selectedYear = $(this).val() || '';
        $('#catalogSearchInput').val(''); // Clear search box when changing year
        $('#btn_clear_search').addClass('d-none');
        loadPublicCatalog(selectedYear);
    });

    $(document).on('input', '#catalogSearchInput', function() {
        renderCatalog();
    });

    $(document).on('click', '#btn_clear_search', function() {
        $('#catalogSearchInput').val('');
        renderCatalog();
    });

    function loadPublicCatalog(year) {
        $('#catalog_loading').show();
        $('#catalog_items').hide().empty();
        $('#catalog_empty').hide();
        $('#catalog_detail_view').hide();
        $('#catalog_list_view').show();

        $.ajax({
            url: 'db.php',
            type: 'GET',
            data: { action: 'get_catalog', year: year },
            dataType: 'json',
            success: function(data) {
                $('#catalog_loading').hide();
                currentCatalogData = data || [];
                renderCatalog();
                $('#catalog_items').fadeIn(200);
            },
            error: function() {
                $('#catalog_loading').hide();
                $('#catalog_empty').text('Σφάλμα κατά τη φόρτωση του καταλόγου.').show();
            }
        });
    }

    $(document).on('click', '#btn_catalog_back', function() {
        $('#catalog_detail_view').fadeOut(200, function() {
            $('#catalog_list_view').fadeIn(200);
            $('html, body').animate({
                scrollTop: $('#catalog_items').offset().top - 100
            }, 300);
        });
    });

    function htmlEntities(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});