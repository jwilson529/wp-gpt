(function($) {
    'use strict';
    jQuery(document).ready(function($) {
        let conversationId = null;

        $('#chatgpt-form').on('submit', function(e) {
            e.preventDefault();

            var user_input = $('#user_input').val();
            $('#user_input').val(''); // Clear the textarea
            $('#chatgpt-response').append('<div class="chat-message user-message">' + user_input + '</div>');
            $('#chatgpt-response').append('<div class="chat-message loading">Loading...</div>');
            scrollToBottom(); // Scroll to the bottom after appending the messages

            console.log('User input:', user_input);

            $.ajax({
                url: chatgpt.ajax_url,
                type: 'POST',
                data: {
                    action: 'chatgpt_submit',
                    user_input: user_input,
                    conversation_id: conversationId,
                    chatgpt_nonce: chatgpt.nonce
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        $('#chatgpt-response .loading').last().remove(); // Remove the loading message
                        var formattedResponse = formatResponse(response.data.response_text);
                        $('#chatgpt-response').append('<div class="chat-message bot-message">' + formattedResponse + '</div>');
                        conversationId = response.data.conversation_id; // Update conversation ID
                    } else {
                        $('#chatgpt-response .loading').last().remove(); // Remove the loading message
                        $('#chatgpt-response').append('<div class="chat-message error-message">Error: ' + response.data + '</div>');
                    }
                    scrollToBottom(); // Scroll to the bottom after appending the bot's response
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX error:', textStatus, errorThrown);
                    $('#chatgpt-response .loading').last().remove(); // Remove the loading message
                    $('#chatgpt-response').append('<div class="chat-message error-message">An error occurred.</div>');
                    scrollToBottom(); // Scroll to the bottom after appending the error message
                }
            });
        });

        // Submit the form when Enter key is pressed without Shift
        $('#user_input').on('keydown', function(e) {
            if (e.keyCode === 13 && !e.shiftKey) {
                e.preventDefault();
                $('#chatgpt-form').submit();
            }
        });

        // Function to format the response text
        function formatResponse(text) {
            // Replace line breaks with <br>
            var formattedText = text.replace(/\n/g, '<br>');
            return formattedText;
        }

        // Function to scroll to the bottom of the chat box
        function scrollToBottom() {
            $('#chatgpt-response').scrollTop($('#chatgpt-response')[0].scrollHeight);
        }

        // Function to update the max tokens limit based on the selected model
        function updateMaxTokensLimit() {
            var model = document.getElementById('chatgpt_model');
            var maxTokensInput = document.getElementById('chatgpt_max_tokens');
            var maxTokensInfo = document.getElementById('max-tokens-info');

            if (!model || !maxTokensInput || !maxTokensInfo) {
                return;
            }

            var maxTokensLimit = 2048; // Default limit

            if (model.value.includes('davinci')) {
                maxTokensLimit = 4096;
            } else if (model.value.includes('curie') || model.value.includes('babbage') || model.value.includes('ada')) {
                maxTokensLimit = 2048;
            } else if (model.value.includes('gpt-3.5') || model.value.includes('gpt-4')) {
                maxTokensLimit = 4096;
            } else if (model.value.includes('gpt-4-32k')) {
                maxTokensLimit = 32768;
            }

            maxTokensInput.max = maxTokensLimit;
            maxTokensInfo.textContent = 'Maximum tokens for ' + model.value + ' is ' + maxTokensLimit + '.';
        }

        // Update max tokens limit on page load and when the model changes
        if (document.getElementById('chatgpt_model')) {
            updateMaxTokensLimit(); // Run on page load
            document.getElementById('chatgpt_model').addEventListener('change', updateMaxTokensLimit);
        }
    });
})(jQuery);
