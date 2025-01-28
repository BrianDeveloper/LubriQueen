class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.initialize();
    }

    initialize() {
        // Crear el botón del widget
        const button = document.createElement('div');
        button.className = 'chat-widget-button';
        button.innerHTML = '<i class="fas fa-comments"></i>';
        button.onclick = () => this.toggleChat();
        document.body.appendChild(button);

        // Crear el contenedor del chat
        const chatWidget = document.createElement('div');
        chatWidget.className = 'chat-widget';
        chatWidget.innerHTML = `
            <div class="chat-header">
                <div>Asistente Virtual LubriQueen</div>
                <div class="chat-close">&times;</div>
            </div>
            <div class="chat-messages"></div>
            <div class="chat-input-container">
                <div class="chat-input-group">
                    <input type="text" class="chat-input" placeholder="Escribe tu pregunta...">
                    <button class="chat-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(chatWidget);

        // Configurar elementos
        this.button = button;
        this.widget = chatWidget;
        this.messagesContainer = chatWidget.querySelector('.chat-messages');
        this.input = chatWidget.querySelector('.chat-input');
        this.sendButton = chatWidget.querySelector('.chat-send');
        this.closeButton = chatWidget.querySelector('.chat-close');

        // Crear el indicador de escritura
        this.typingIndicator = document.createElement('div');
        this.typingIndicator.className = 'typing-indicator';
        this.typingIndicator.innerHTML = '<span></span><span></span><span></span>';
        this.messagesContainer.appendChild(this.typingIndicator);

        // Configurar eventos
        this.closeButton.onclick = () => this.toggleChat();
        this.sendButton.onclick = () => this.sendMessage();
        this.input.onkeypress = (e) => {
            if (e.key === 'Enter') this.sendMessage();
        };

        // Agregar mensaje inicial
        this.addMessage('¡Hola! Soy el asistente virtual de LubriQueen. ¿En qué puedo ayudarte con respecto a nuestros lubricantes?', false);
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        this.button.classList.toggle('active');
        this.widget.classList.toggle('active');
        
        if (this.isOpen) {
            this.input.focus();
            this.scrollToBottom();
        }
    }

    showTypingIndicator() {
        this.typingIndicator.classList.add('active');
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    hideTypingIndicator() {
        this.typingIndicator.classList.remove('active');
    }

    addMessage(text, isUser) {
        const message = document.createElement('div');
        message.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
        message.textContent = text;
        // Insertar el mensaje antes del indicador de escritura
        this.messagesContainer.insertBefore(message, this.typingIndicator);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    async sendMessage() {
        const message = this.input.value.trim();
        if (!message) return;

        this.addMessage(message, true);
        this.input.value = '';
        this.input.disabled = true;
        this.showTypingIndicator();

        // Establecer un timeout para la respuesta
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error('Timeout')), 15000);
        });

        try {
            const response = await Promise.race([
                fetch('chatbot/chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                }),
                timeoutPromise
            ]);

            const data = await response.json();
            this.hideTypingIndicator();
            
            if (data.error) {
                // Si hay un error específico de la API
                this.addMessage('Lo siento, hubo un problema. ¿Podrías reformular tu pregunta?', false);
            } else if (data.response) {
                this.addMessage(data.response, false);
            } else {
                // Si no hay respuesta válida
                this.addMessage('Permíteme ayudarte con información sobre nuestros lubricantes. ¿Qué te gustaría saber específicamente?', false);
            }
        } catch (error) {
            this.hideTypingIndicator();
            if (error.message === 'Timeout') {
                this.addMessage('Lo siento, estoy tardando más de lo normal. Por favor, intenta de nuevo.', false);
            } else {
                this.addMessage('Hubo un problema de conexión. Por favor, intenta de nuevo.', false);
            }
        }

        this.input.disabled = false;
        this.input.focus();
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
}

// Inicializar el widget cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    new ChatWidget();
});
