package com.hierarchychat.activities

import android.content.Intent
import android.os.Bundle
import android.view.Menu
import android.view.MenuItem
import android.view.View
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.hierarchychat.R
import com.hierarchychat.adapters.ConversationAdapter
import com.hierarchychat.api.ApiClient
import com.hierarchychat.databinding.ActivityMainBinding
import com.hierarchychat.models.Conversation
import com.hierarchychat.utils.SessionManager
import com.hierarchychat.utils.Utils
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {
    
    private lateinit var binding: ActivityMainBinding
    private val conversationAdapter = ConversationAdapter { conversation ->
        openPrivateChat(conversation)
    }
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        setupToolbar()
        setupRecyclerView()
        setupFab()
        loadConversations()
    }
    
    override fun onResume() {
        super.onResume()
        loadConversations()
    }
    
    private fun setupToolbar() {
        setSupportActionBar(binding.toolbar)
        val user = SessionManager.getUser()
        supportActionBar?.title = "Messages"
        supportActionBar?.subtitle = "${user?.name} (${Utils.getRoleDisplayName(user?.role ?: "")})"
    }
    
    private fun setupRecyclerView() {
        binding.recyclerView.apply {
            layoutManager = LinearLayoutManager(this@MainActivity)
            adapter = conversationAdapter
        }
        
        binding.swipeRefresh.setOnRefreshListener {
            loadConversations()
        }
    }
    
    private fun setupFab() {
        binding.fabCreate.setOnClickListener {
            showNewMessageDialog()
        }
    }
    
    private fun loadConversations() {
        lifecycleScope.launch {
            try {
                binding.swipeRefresh.isRefreshing = true
                val response = ApiClient.getApiService().getConversations()
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val conversations = response.body()?.data ?: emptyList()
                    conversationAdapter.submitList(conversations)
                    
                    binding.emptyView.visibility = if (conversations.isEmpty()) View.VISIBLE else View.GONE
                    binding.emptyView.text = "No messages yet.\nTap + to start a conversation."
                }
            } catch (e: Exception) {
                Utils.showToast(this@MainActivity, "Error: ${e.message}")
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }
    
    private fun openPrivateChat(conversation: Conversation) {
        val intent = Intent(this, ChatActivity::class.java).apply {
            putExtra("chat_type", "private")
            putExtra("user_id", conversation.user.id)
            putExtra("user_name", conversation.user.name)
            putExtra("user_role", conversation.user.role)
        }
        startActivity(intent)
    }
    
    private fun showNewMessageDialog() {
        lifecycleScope.launch {
            try {
                val response = ApiClient.getApiService().getMessageableUsers()
                if (response.isSuccessful && response.body()?.success == true) {
                    val users = response.body()?.data ?: emptyList()
                    if (users.isEmpty()) {
                        Utils.showToast(this@MainActivity, "No users available to message")
                        return@launch
                    }
                    
                    val userNames = users.map { "${it.name} (${Utils.getRoleDisplayName(it.role)})" }.toTypedArray()
                    
                    AlertDialog.Builder(this@MainActivity)
                        .setTitle("New Message")
                        .setItems(userNames) { _, which ->
                            val selectedUser = users[which]
                            val intent = Intent(this@MainActivity, ChatActivity::class.java).apply {
                                putExtra("chat_type", "private")
                                putExtra("user_id", selectedUser.id)
                                putExtra("user_name", selectedUser.name)
                                putExtra("user_role", selectedUser.role)
                            }
                            startActivity(intent)
                        }
                        .setNegativeButton("Cancel", null)
                        .show()
                }
            } catch (e: Exception) {
                Utils.showToast(this@MainActivity, "Error: ${e.message}")
            }
        }
    }
    
    private fun performLogout() {
        AlertDialog.Builder(this)
            .setTitle("Logout")
            .setMessage("Are you sure?")
            .setPositiveButton("Logout") { _, _ ->
                lifecycleScope.launch {
                    try {
                        ApiClient.getApiService().logout()
                    } catch (e: Exception) {}
                    SessionManager.logout()
                    ApiClient.resetClient()
                    startActivity(Intent(this@MainActivity, LoginActivity::class.java))
                    finish()
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }
    
    override fun onCreateOptionsMenu(menu: Menu?): Boolean {
        menuInflater.inflate(R.menu.menu_main, menu)
        return true
    }
    
    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        return when (item.itemId) {
            R.id.action_logout -> {
                performLogout()
                true
            }
            else -> super.onOptionsItemSelected(item)
        }
    }
}
