
<div id="chatbot-container" class="fixed bottom-6 right-6 z-50">
    <button id="chatbot-toggle"
            onclick="toggleChatbot()"
            class="w-14 h-14 rounded-full bg-gradient-to-r from-neon-cyan to-neon-purple shadow-2xl shadow-neon-cyan/50 hover:scale-110 transition-transform flex items-center justify-center">
        <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
    </button>

    <div id="chatbot-window" class="hidden absolute bottom-20 right-0 w-96 h-[500px] glass-strong rounded-2xl flex flex-col overflow-hidden">
        <div class="bg-gradient-to-r from-neon-cyan to-neon-purple p-4 flex justify-between items-center">
            <div>
                <div data-i18n="chat_assistant" class="font-display font-bold text-black">SumateraMall Assistant</div>
                <div data-i18n="chat_powered_by" class="text-xs text-black/70">Didukung SPARQL</div>
            </div>
            <button onclick="toggleChatbot()" class="text-black hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3">
            <div id="chatbot-greeting" class="flex gap-2">
                <div class="glass p-3 rounded-2xl rounded-tl-none max-w-[80%] text-sm">
                    <span data-i18n="chat_greeting">Halo! Saya asisten virtual SumateraMall. Coba tanya:</span><br>
                    <ul class="mt-2 space-y-1 text-xs text-gray-300">
                        <li data-i18n="chat_example_cafe">- Cari cafe di Sun Plaza</li>
                        <li data-i18n="chat_example_malls">- Mall di Medan</li>
                        <li data-i18n="chat_example_open">- Toko yang masih buka</li>
                        <li data-i18n="chat_example_top">- Toko dengan rating tertinggi</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="p-3 border-t border-white/10 flex gap-2">
            <input id="chatbot-input" type="text" data-i18n-placeholder="chat_placeholder"
                   onkeypress="if(event.key==='Enter')sendMessage()"
                   placeholder="Ketik pertanyaan..."
                   class="flex-1 glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none text-sm">

            <button onclick="sendMessage()" class="bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-4 rounded-xl hover:shadow-lg hover:shadow-neon-cyan/50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
let sessionId = 'sess_' + Math.random().toString(36).substring(2, 15);

const chatHistoryUrl = @json(auth()->check() ? route('chatbot.history') : null);

let historyLoaded = false;
let historyLoading = false;

function toggleChatbot() {
    const chatbotWindow = document.getElementById('chatbot-window');

    chatbotWindow.classList.toggle('hidden');

    if (!chatbotWindow.classList.contains('hidden')) {
        loadChatHistory();
    }
}

function addMessage(text, isUser = false) {
    const container = document.getElementById('chatbot-messages');
    const div = document.createElement('div');

    div.className = 'flex ' + (isUser ? 'justify-end' : 'justify-start');

    const bubble = document.createElement('div');

    bubble.className = `${isUser
        ? 'bg-gradient-to-r from-neon-cyan to-neon-purple text-black rounded-tr-none'
        : 'glass rounded-tl-none'} p-3 rounded-2xl max-w-[80%] text-sm whitespace-pre-wrap`;

    bubble.textContent = text;

    div.append(bubble);
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

async function loadChatHistory() {
    // Guest tidak mengambil riwayat akun.
    if (!chatHistoryUrl || historyLoaded || historyLoading) {
        return;
    }

    historyLoading = true;

    try {
        const response = await fetch(chatHistoryUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Gagal memuat riwayat chat.');
        }

        const data = await response.json();
        const history = data.history || [];

        if (history.length > 0) {
            document.getElementById('chatbot-greeting')?.remove();

            history.forEach(chat => {
                addMessage(chat.message, true);
                addMessage(chat.reply, false);
            });
        }

        historyLoaded = true;

    } catch (error) {
        console.error('Chat history error:', error);
    } finally {
        historyLoading = false;
    }
}

async function sendMessage() {
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();

    if (!message) return;

    addMessage(message, true);
    input.value = '';

    const loading = document.createElement('div');
    loading.id = 'loading-indicator';
    loading.className = 'flex justify-start';

    const loadingBubble = document.createElement('div');
    loadingBubble.className = 'glass p-3 rounded-2xl text-sm';
    loadingBubble.textContent = window.sumateraTranslate('chat_thinking');

    loading.appendChild(loadingBubble);
    document.getElementById('chatbot-messages').appendChild(loading);

    try {
        const res = await fetch('{{ route("chatbot.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                message,
                session_id: sessionId,
                locale: document.documentElement.lang
            })
        });

        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.message || 'Layanan pencarian tidak tersedia.');
        }

        document.getElementById('loading-indicator')?.remove();

        addMessage(data.reply);

        if (data.notice) {
            addMessage(data.notice);
        }

    } catch (e) {
        document.getElementById('loading-indicator')?.remove();
        addMessage(e.message || 'Maaf, terjadi kesalahan koneksi.');
    }
}
</script>
