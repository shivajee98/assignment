package com.hierarchychat.adapters

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.hierarchychat.R
import com.hierarchychat.models.Conversation
import com.hierarchychat.utils.Utils

class ConversationAdapter(
    private val onItemClick: (Conversation) -> Unit
) : ListAdapter<Conversation, ConversationAdapter.ViewHolder>(DiffCallback()) {
    
    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_conversation, parent, false)
        return ViewHolder(view)
    }
    
    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(getItem(position))
    }
    
    inner class ViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvName: TextView = itemView.findViewById(R.id.tvName)
        private val tvLastMessage: TextView = itemView.findViewById(R.id.tvLastMessage)
        private val tvTime: TextView = itemView.findViewById(R.id.tvTime)
        private val tvInitials: TextView = itemView.findViewById(R.id.tvInitials)
        private val tvRole: TextView = itemView.findViewById(R.id.tvRole)
        
        fun bind(conversation: Conversation) {
            tvName.text = conversation.user.name
            tvRole.text = Utils.getRoleDisplayName(conversation.user.role)
            tvInitials.text = Utils.getInitials(conversation.user.name)
            
            val msg = conversation.lastMessage
            tvLastMessage.text = if (msg.isFromMe) "You: ${msg.content}" else msg.content
            tvTime.text = Utils.formatChatTime(msg.createdAt)
            
            itemView.setOnClickListener { onItemClick(conversation) }
        }
    }
    
    class DiffCallback : DiffUtil.ItemCallback<Conversation>() {
        override fun areItemsTheSame(oldItem: Conversation, newItem: Conversation) =
            oldItem.user.id == newItem.user.id
        override fun areContentsTheSame(oldItem: Conversation, newItem: Conversation) =
            oldItem == newItem
    }
}
