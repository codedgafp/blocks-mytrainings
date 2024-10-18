/**
 * Javascript containing function of the block mytrainings
 */
define([
    'jquery',
    'local_mentor_core/pagination',
    "format_edadmin/format_edadmin"
], function ($, pagination, format_edadmin) {
    var block_mytrainings = {
        /**
         * Init JS
         */
        init: function (blockopen) {
            this.blockopen = blockopen;
            var that = this;
            // Event for open or close trainings block
            $(document).ready(function (){
                $('.block_mytrainings .open-block').on('click', function (e) {
                    var openButton = $(e.currentTarget);
                    var blockMyTrainings = $(e.currentTarget).closest('.block_mytrainings')[0];
                    if (blockMyTrainings.classList.contains('hidden-block')) {
                        // Open block
                        blockMyTrainings.classList.remove('hidden-block');
                        openButton.html('<button class="button-showless-showmore" aria-expanded="true">' + M.util.get_string('showless', 'block_mytrainings') + ' -</button>');
                        that.setUserPreference(1);
                        // The block must be visible to add images.
                        that.addImages();
                    } else {
                        // Close block
                        blockMyTrainings.classList.add('hidden-block');
                        openButton.html('<button class="button-showless-showmore" aria-expanded="false">' + M.util.get_string('showmore', 'block_mytrainings') + ' +</button>');
                        that.setUserPreference(0);
                    }
                });
            })
        
            // Init training block pagination
            // 12 trainings per page
            pagination.initPagination($('.block_mytrainings .training-tile'), $('#mytrainings-pagination'), 12, true, this.addImages);

            // Redirect link event.
            $('.block_mytrainings .training-more-information').on('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var trainingId = $(event.currentTarget).data().trainingId;
                window.location.href = M.cfg.wwwroot + '/local/trainings/pages/training.php?trainingid=' + trainingId;
            });

            // Favourite action
            $('.block_mytrainings .fav').on('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var currentTarget = $(event.currentTarget);
                var trainingId = currentTarget.parent().data('trainingId');
                if ($(currentTarget).hasClass('fa-star-o')) {
                    that.addFavourite(currentTarget, trainingId);
                } else {
                    that.removeFavourite(currentTarget, trainingId);
                }
            });

            $('.block_mytrainings .fav').keypress(function (event) {
                if (event.which == 13) {
                    var currentTarget = $(event.currentTarget);
                    $(currentTarget).click();
                }
            });
        },
        /**
         * Set user preference
         *
         * @param value
         */
        setUserPreference: function (value) {
            $.ajax({
                url: M.cfg.wwwroot + '/local/user/ajax/ajax.php',
                data: {
                    controller: 'user',
                    action: 'set_user_preference',
                    preferencename: 'block_mytrainings_open',
                    value: value
                }
            });
        },
        /**
         * Add images into trainings tiles
         */
        addImages: function () {
            $('.block_mytrainings .training-tile:visible').each(function () {
                // Adding image.
                var thumbnailDiv = $(this).find('div.training-tile-thumbnail-resize');
                var thumbnailUrl = thumbnailDiv.attr('data-thumbnail-url');
                thumbnailDiv.css('background-image', 'url(' + thumbnailUrl + ')');
            });
        },
        /**
         * Add training to user's favourite
         *
         * @param {jquery} currentTarget
         * @param {int} trainingid
         */
        addFavourite: function (currentTarget, trainingid) {
            // Call add training to user's favourite function
            format_edadmin.ajax_call({
                url: M.cfg.wwwroot + '/blocks/mytrainings/ajax/ajax.php',
                plugintype: 'blocks',
                controller: 'training_favourite',
                action: 'add_favourite',
                format: 'json',
                trainingid: trainingid,
                callback: function (response) {

                    response = JSON.parse(response);

                    if (!response.success) {
                        console.error(response.message);
                        return;
                    }

                    var responseData = response.message;

                    if (responseData) {
                        // Hidden add favourite button
                        $(currentTarget).removeClass('fa-star-o');
                        $(currentTarget).addClass('fa-star');
                        $(currentTarget).prop('title', M.util.get_string('removefavourite', 'block_mytrainings'));
                    }
                }
            });
        },
        /**
         * Add training to user's favourite
         *
         * @param {jquery} currentTarget
         * @param {int} trainingid
         */
        removeFavourite: function (currentTarget, trainingid) {
            // Call remove training to user's favourite function
            format_edadmin.ajax_call({
                url: M.cfg.wwwroot + '/blocks/mytrainings/ajax/ajax.php',
                plugintype: 'blocks',
                controller: 'training_favourite',
                action: 'remove_favourite',
                format: 'json',
                trainingid: trainingid,
                callback: function (response) {

                    response = JSON.parse(response);

                    if (!response.success) {
                        console.error(response.message);
                        return;
                    }

                    var responseData = response.message;

                    if (responseData) {
                        // Hidden remove favourite button
                        $(currentTarget).removeClass('fa-star');
                        $(currentTarget).addClass('fa-star-o');
                        $(currentTarget).prop('title', M.util.get_string('addfavourite', 'block_mytrainings'));
                    }
                }
            });
        }
    };

    //add object to window to be called outside require
    window.block_mytrainings = block_mytrainings;
    return block_mytrainings;
});
