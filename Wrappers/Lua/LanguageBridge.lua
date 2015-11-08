--[[
	PHP Language Bridge

	@package Wrapper::Lua
	@version 0.1

	@author @fermino <fermino.github@gmail.com>
--]]

	-- JSON library

	JSON = require('Wrappers/Lua/JSON/json')

	function Throw(Message, Code)
		ErrorMessage = "Can't decode protocol node"

		if type(Message) == 'string' and type(Code) == 'number' then
			ErrorMessage = ErrorMessage .. string.format(' (%s => 0x%x)', Message, Code)
		elseif type(Message) == 'string' then
			ErrorMessage = ErrorMessage .. string.format(' (%s)', Message)
		elseif type(Code) == 'number' then
			ErrorMessage = ErrorMessage .. string.format(' (0x%x)', Code)
		end

		error(ErrorMessage)
	end

	__Process = {}

	while true do
		-- Read the JSON encoded node
		ProtocolNode = io.read()

		if ProtocolNode ~= '' then
			-- Decode the JSON node
			ProtocolNode = JSON.decode(ProtocolNode)

			-- If ProtocolNode is an array
			if type(ProtocolNode) == 'table' then
				-- If ProtocolNode.Type is a number
				if type(ProtocolNode.Type) == 'number' then
					-- If ProtocolNode.Data is an array
					if type(ProtocolNode.Data) == 'table' then
						-- If ProtocolNode.Type is 0x00 => Script filename
						if ProtocolNode.Type == 0x00 then
							if type(ProtocolNode.Data.Filename) == 'string' then
								__Process.Filename = ProtocolNode.Data.Filename
							else
								Throw('ProtocolNode.Data.Filename must be a string', ProtocolNode.Type)
							end
						-- If ProtocolNode.Type is 0x10 => Variable
						elseif ProtocolNode.Type == 0x10 then
							if type(ProtocolNode.Data.Name) == 'string' then
								if ProtocolNode.Data.Name ~= '' then
									if type(ProtocolNode.Data.Value) ~= 'nil' then
										_G[ProtocolNode.Data.Name] = ProtocolNode.Data.Value
									else
										Throw('ProtocolNode.Data.Value must not be empty', ProtocolNode.Type)
									end
								else
									Throw('ProtocolNode.Data.Name must not be empty', ProtocolNode.Type)
								end
							else
								Throw('ProtocolNode.Data.Name must be a string', ProtocolNode.Type)
							end
						-- If ProtocolNode.Type is 0xff => Execute
						elseif ProtocolNode.Type == 0xff then
							break
						-- If ProtocolNode.Type is unknown
						else
							Throw('ProtocolNode.Type is unknown', ProtocolNode.Type)
						end
					else
						Throw('ProtocolNode.Data must be an array', ProtocolNode.Type)
					end
				else
					Throw('ProtocolNode.Type must be an integer', ProtocolNode.Type)
				end
			else
				Throw('ProtocolNode must be an array', nil)
			end
		else
			Throw('ProtocolNode must be a JSON encoded array', nil)
		end
	end

	ProtocolNode = nil
	Throw = nil

	if type(__Process.Filename) == 'string' then
		dofile(__Process.Filename)
	else
		error('You must send a ProtocolNode (0x00) containing the script filename as string')
	end