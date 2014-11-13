(function($) {
    if (num_recent_submissions == null) var num_recent_submissions = 20;

    $(function () {
        $("#no_search_results").hide();
        $("#search_results_table").hide();

        $("#search_btn").bind("click", function () {
            searchForms($("#search_query").val());
        });

        $("#search_query").keypress(function (e) {
            if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                searchForms($("#search_query").val());
                return false;
            } else {
                return true;
            }

        });
        // get recent product results
        $.ajax({
            type: "POST",
            url: "/admin/admin_contact_form_submissions/ajax_get_list",
            //data: ({action: "get recent form submissions", page: 1, num: num_recent_submissions}),
            async: false,
            success: function(json_result) {
                renderRecentFormSubmissions($.parseJSON(json_result).results);
            }
        });

        $("#del").live('click', function(){
            var id = $(this).attr("href");
            if(confirm("Are you sure you want to delete the selected items?")) {
                $.ajax({
                   type: "POST",
                   url:  "/admin/admin_contact_form_submissions/ajax_delete",
                   data: ({ 'data[ContactSubmission][id]':id }),
                   async: false,
                   success: function(json_result) {
                       //console.log(json_result)
                        if (json_result.status) {

                            $("#row-" + id).fadeOut();
                        }
                   }
                });
            }

            return false;
        });

    });


    function searchForms ($query) {
        $.ajax({
            type: "POST",
            url: "/admin/admin_contact_form_submissions/ajax_search_list",
            data: ({'data[Search][query]': $query}),
            async: false,
            success: function(json_result) {
                renderSearchResults($.parseJSON(json_result).results);
            }
        });
    }

    function renderSearchResults ($data) {
        $("#search_results_table tbody").html("");
        $("#search_results_table").hide();
        $("#no_search_results").hide();
        var num_results = $data.length;
        if (num_results) {
            for (var i=0; i<num_results; i++) {
                $("#search_results_table tbody").append("<tr id='row-" + $data[i].id + "'><td>"  + $data[i].last_name + ", " + $data[i].first_name + "</td><td>" + $data[i].email + "</td><td>" + $data[i].pretty_date + "</td><td>" + $data[i].num_views + "</td><td>" + $data[i].num_comments + "</td><td><span  style=\"cursor: pointer;\" onclick=\"showFormDetails(" + $data[i].id + ");\"> [Show]&nbsp;&nbsp;&nbsp; </span><a id='del' href='" + $data[i].id + "'>[Del]</a> </td></tr>");
            }
            $("#search_results_table").fadeIn();
        } else {
            $("#no_search_results").fadeIn();
        }
    }


    function renderRecentFormSubmissions ($data) {
        $("#recent_results table").hide();
        var num_results = $data.length;
        for (var i=0; i<num_results; i++) {
            $("#recent_results tbody").append("<tr id='row-" + $data[i].id + "'><td>" + $data[i].first_name  + "</td><td>" + $data[i].email + "</td><td>" + $data[i].pretty_date + "</td><td>" + $data[i].num_views + "</td><td>" + $data[i].num_comments + "</td><td><span  style=\"cursor: pointer;\" onclick=\"showFormDetails(" + $data[i].id + ");\"> [Show]&nbsp;&nbsp;&nbsp; </span><a id='del' href='" + $data[i].id + "'>[Del]</a>  </td></tr>");
        }
        $("#recent_results table").fadeIn();
    }
})(jQuery);

function showFormDetails ($id) {
    document.location.href = "/admin/admin_contact_form_submissions/view/" + $id;
}