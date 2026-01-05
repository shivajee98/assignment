package com.hierarchychat.activities

import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.hierarchychat.adapters.MessageAdapter
import com.hierarchychat.api.ApiClient
import com.hierarchychat.databinding.ActivityChatBinding
import com.hierarchychat.models.SendMessageRequest
import com.hierarchychat.utils.SessionManager
import com.hierarchychat.utils.Utils
import kotlinx.coroutines.launch

class ChatActivity : AppCompatActivity() {
    
    private lateinit var binding: ActivityChatBinding
    private lateinit var messageAdapter: MessageAdapter
    
    private var chatType: String = "private"
    private var userId: Long = 0
    private var groupId: Long = 0
    private var userName: String = ""
    private var groupName: String = ""
    private var userRole: String = ""
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityChatBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        chatType = intent.getStringExtra("chat_type") ?: "private"
        userId = intent.getLongExtra("user_id", 0)
        groupId = intent.getLongExtra("group_id", 0)
        userName = intent.getStringExtra("user_name") ?: ""
        groupName = intent.getStringExtra("group_name") ?: ""
        userRole = intent.getStringExtra("user_role") ?: ""
        
        setupToolbar()
        setupRecyclerView()
        setupMessageInput()
        loadMessages()
    }
    
    private fun setupToolbar() {
        setSupportActionBar(binding.toolbar)
        supportActionBar?.setDisplayHomeAsUpEnabled(true)
        
        if (chatType == "private") {
            supportActionBar?.title = userName
            supportActionBar?.subtitle = Utils.getRoleDisplayName(userRole)
        } else {
            supportActionBar?.title = groupName
        }
        
        binding.toolbar.setNavigationOnClickListener { finish() }
    }
    
    private fun setupRecyclerView() {
        messageAdapter = MessageAdapter(
            currentUserId = SessionManager.getUserId(),
            onMessageClick = {},
            onReplyClick = {}
        )
        
        binding.recyclerViewMessages.apply {
            layoutManager = LinearLayoutManager(this@ChatActivity).apply {
                stackFromEnd = true
            }
            adapter = messageAdapter
        }
        
        binding.swipeRefresh.setOnRefreshListener {
            loadMessages()
        }
    }
    
    private fun setupMessageInput() {
        binding.btnSend.setOnClickListener {
            sendMessage()
        }
        
        binding.replyContainer.visibility = View.GONE
    }
    
    private fun loadMessages() {
        lifecycleScope.launch {
            try {
                binding.swipeRefresh.isRefreshing = true
                
                val response = if (chatType == "private") {
                    ApiClient.getApiService().getPrivateMessages(userId)
                } else {
                    ApiClient.getApiService().getGroupMessages(groupId)
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val messages = response.body()?.data ?: emptyList()
                    messageAdapter.submitList(messages)
                    
                    if (messages.isNotEmpty()) {
                        binding.recyclerViewMessages.scrollToPosition(messages.size - 1)
                    }
                    
                    binding.emptyView.visibility = if (messages.isEmpty()) View.VISIBLE else View.GONE
                }
            } catch (e: Exception) {
                Utils.showToast(this@ChatActivity, "Error: ${e.message}")
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }
    
    private fun sendMessage() {
        val content = binding.etMessage.text.toString().trim()
        if (content.isEmpty()) return
        
        binding.btnSend.isEnabled = false
        
        lifecycleScope.launch {
            try {
                val request = SendMessageRequest(content = content)
                
                val response = if (chatType == "private") {
                    ApiClient.getApiService().sendPrivateMessage(userId, request)
                } else {
                    ApiClient.getApiService().sendGroupMessage(groupId, request)
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    binding.etMessage.text?.clear()
                    loadMessages()
                } else {
                    Utils.showToast(this@ChatActivity, response.body()?.message ?: "Failed to send")
                }
            } catch (e: Exception) {
                Utils.showToast(this@ChatActivity, "Error: ${e.message}")
            } finally {
                binding.btnSend.isEnabled = true
            }
        }
    }
}
